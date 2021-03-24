<?php

use MiraklSeller_Core_Model_Offer as Offer;

class MiraklSeller_Core_Block_Adminhtml_Listing_Dashboard extends Mage_Adminhtml_Block_Template
{
    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * @return  MiraklSeller_Core_Model_Listing
     */
    public function getListing()
    {
        return Mage::registry('mirakl_seller_listing');
    }

    /**
     * Initialization
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_offerResource = Mage::getResourceModel('mirakl_seller/offer');
    }

    /**
     * @return  int
     */
    public function getNbSuccessProducts()
    {
        return $this->_offerResource->getNbListingSuccessProducts($this->getListing()->getId());
    }

    /**
     * @return  array
     */
    public function getFailedProductsLabels()
    {
        $failedProducts = $this->_offerResource->getNbListingFailedProductsByStatus($this->getListing()->getId());
        $failedOffers   = $this->_offerResource->getNbListingFailedOffers($this->getListing()->getId());

        if (!empty($failedProducts)) {
            foreach (Offer::getProductStatusLabels() as $status => $label) {
                if (array_key_exists($status, $failedProducts)) {
                    $failedProducts[$status]['product_import_status'] = $label;
                }
            }
        }

        if (!empty($failedOffers['count'])) {
            $failedProducts[Offer::OFFER_ERROR]['product_import_status'] = 'Import price & stock failed';
            $failedProducts[Offer::OFFER_ERROR]['count'] = $failedOffers['count'];
        }

        return $failedProducts;
    }

    /**
     * {@inheritdoc}
     */
    public function getGridFilterUrl()
    {
        return $this->getUrl('*/*/productGrid', array('_current' => true)) . '/listing_product_filter/';
    }
}
