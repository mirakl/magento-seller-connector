<?php

use Mage_Catalog_Model_Resource_Product_Collection as ProductCollection;
use MiraklSeller_Core_Model_Listing as Listing;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Helper_Listing extends MiraklSeller_Core_Helper_Data
{
    /**
     * @param   Listing             $listing
     * @param   ProductCollection   $collection
     * @param   bool                $joinLeft
     * @return  $this
     */
    public function addListingPriceDataToCollection(Listing $listing, ProductCollection $collection, $joinLeft = false)
    {
        /** @var MiraklSeller_Core_Helper_Config $config */
        $config = Mage::helper('mirakl_seller/config');

        $collection->setStore($listing->getStoreId());
        $collection->addPriceData($config->getCustomerGroup(), $listing->getWebsiteId());

        if ($joinLeft) {
            $fromPart = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
            if (isset($fromPart['price_index'])) {
                $fromPart['price_index']['joinType'] = 'left join';
                $collection->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
            }
        }

        return $this;
    }

    /**
     * Export the specified listing asynchronously (export products or offers or both)
     *
     * @param   Listing $listing
     * @param   string  $exportType
     * @param   bool    $offerFull
     * @param   string  $productMode
     * @param   string  $processType
     * @return  Process[]
     */
    public function export(
        Listing $listing,
        $exportType = Listing::TYPE_ALL,
        $offerFull = true,
        $productMode = Listing::PRODUCT_MODE_PENDING,
        $processType = Process::TYPE_ADMIN
    ) {
        $config = Mage::helper('mirakl_seller/config');

        $processes = array();

        if ($exportType == Listing::TYPE_PRODUCT || $exportType == Listing::TYPE_ALL) {
            $processes[] = Mage::getModel('mirakl_seller_process/process')
                ->setType($processType)
                ->setName('Export listing products')
                ->setHelper('mirakl_seller/listing_process')
                ->setMethod('exportProduct')
                ->setParams(array($listing->getId(), $productMode, $config->isAutoCreateTracking()))
                ->save();
        }

        if ($exportType == Listing::TYPE_OFFER || $exportType == Listing::TYPE_ALL) {
            $processes[] = Mage::getModel('mirakl_seller_process/process')
                ->setType($processType)
                ->setName('Export listing offers')
                ->setHelper('mirakl_seller/listing_process')
                ->setMethod('exportOffer')
                ->setParams(array($listing->getId(), $offerFull, $config->isAutoCreateTracking()))
                ->save();
        }

        return $processes;
    }

    /**
     * Refresh the specified listing asynchronously
     *
     * @param   Listing $listing
     * @param   string  $processType
     * @return  Process
     */
    public function refresh(Listing $listing, $processType = Process::TYPE_ADMIN)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType($processType)
            ->setName('Listing refresh')
            ->setHelper('mirakl_seller/listing_process')
            ->setMethod('refresh')
            ->setParams(array($listing->getId()))
            ->save();

        return $process;
    }
}
