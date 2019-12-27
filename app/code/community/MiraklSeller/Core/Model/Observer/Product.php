<?php

use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Model_Observer_Product
{
    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * @var MiraklSeller_Core_Helper_Config
     */
    protected $_config;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_offerResource = Mage::getResourceModel('mirakl_seller/offer');
        $this->_config = Mage::helper('mirakl_seller/config');
    }

    /**
     * Handle mass products disable event to delete associated listings offers as well
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onProductAttributeUpdateBefore(Varien_Event_Observer $observer)
    {
        $data = $observer->getEvent()->getAttributesData();

        if (isset($data['status']) && $data['status'] === Mage_Catalog_Model_Product_Status::STATUS_DISABLED) {
            $productIds = $observer->getEvent()->getProductIds();
            $this->_deleteProducts($productIds);
        }
    }

    /**
     * Catch single product deletion and delete listings offers that have this product linked
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onProductDeleteBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getData('product');

        $this->_deleteProducts(array($product->getId()));
    }

    /**
     * Catch mass products delete action from Magento admin and delete listings offers that have these products linked
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onProductMassDeleteBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Controller_Action $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $action->getRequest();

        $productIds = $request->getParam('product', array());

        if (!empty($productIds)) {
            $this->_deleteProducts($productIds);
        }
    }

    /**
     * Handle products disable event to delete associated listings offers as well
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onProductSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();

        if ($product->isDisabled()) {
            $this->_deleteProducts(array($product->getId()));
        }
    }

    /**
     * @return  Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param   array   $productIds
     */
    protected function _deleteProducts(array $productIds)
    {
        try {
            // Do not try to delete a product twice
            static $deletedProductIds = [];

            $productIds = array_diff($productIds, $deletedProductIds);
            $deletedProductIds = array_merge($deletedProductIds, $productIds);

            $listingIds = $this->_offerResource->getListingIdsByProductIds($productIds);

            if (empty($listingIds)) {
                return;
            }

            $listings = Mage::getModel('mirakl_seller/listing')->getCollection()
                ->addIdFilter($listingIds);

            /** @var MiraklSeller_Core_Model_Listing $listing */
            foreach ($listings as $listing) {
                $listing->setProductIds($productIds);

                // Do not delete offers in Mirakl but define them to qty 0
                $this->_offerResource->markOffersAsDelete($listing->getId(), $productIds);

                /** @var Process $process */
                $process = Mage::getModel('mirakl_seller_process/process')
                    ->setType(Process::TYPE_ADMIN)
                    ->setName('Delete listing offers')
                    ->setHelper('mirakl_seller/listing_process')
                    ->setMethod('exportOffer')
                    ->setParams([$listing->getId(), true, $this->_config->isAutoCreateTracking(), $productIds])
                    ->save();

                // Run process synchronously
                $process->run(true);
            }
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
    }
}