<?php

use MiraklSeller_Core_Model_Listing as Listing;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Helper_Tracking extends MiraklSeller_Core_Helper_Data
{
    /**
     * @param   array   $trackingIds
     * @param   string  $processType
     * @return  Process
     */
    public function updateOfferTrackings($trackingIds, $processType = Process::TYPE_ADMIN)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType($processType)
            ->setName('Update tracking offers import status (OF02)')
            ->setHelper('mirakl_seller/tracking_process')
            ->setMethod('updateOffersImportStatus')
            ->setParams(array($trackingIds))
            ->save();

        return $process;
    }

    /**
     * @param   array   $trackingIds
     * @param   string  $processType
     * @return  Process
     */
    public function updateProductTrackings($trackingIds, $processType = Process::TYPE_ADMIN)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType($processType)
            ->setName('Update tracking products import status (P42)')
            ->setHelper('mirakl_seller/tracking_process')
            ->setMethod('updateProductsImportStatus')
            ->setParams(array($trackingIds))
            ->save();

        return $process;
    }

    /**
     * @param   int     $listingId
     * @param   string  $updateType
     * @param   string  $processType
     * @return  Process[]
     */
    public function updateListingTrackingsByType($listingId, $updateType = Listing::TYPE_ALL, $processType = Process::TYPE_ADMIN)
    {
        $processes = array();

        if ($updateType == Listing::TYPE_OFFER || $updateType == Listing::TYPE_ALL) {
            /** @var MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer_Collection $collection */
            $collection = Mage::getModel('mirakl_seller/listing_tracking_offer')->getCollection();
            $collection->addListingFilter($listingId)
                ->addExcludeOfferStatusCompleteFilter()
                ->addWithImportIdFilter();

            // Update the offer export trackings
            $processes[] = $this->updateOfferTrackings($collection->getAllIds(), $processType);
        }

        if ($updateType == Listing::TYPE_PRODUCT || $updateType == Listing::TYPE_ALL) {
            /** @var MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection $collection */
            $collection = Mage::getModel('mirakl_seller/listing_tracking_product')->getCollection();
            $collection->addListingFilter($listingId)
                ->addExcludeProductStatusFinalFilter()
                ->addWithImportIdFilter();

            // Update the product export trackings
            $processes[] = $this->updateProductTrackings($collection->getAllIds(), $processType);
        }

        return $processes;
    }

    /**
     * @param   array   $trackingIds
     * @param   string  $updateType
     * @param   string  $processType
     * @return  Process[]
     */
    public function updateTrackingsByType($trackingIds, $updateType = Listing::TYPE_ALL, $processType = Process::TYPE_ADMIN)
    {
        $processes = array();

        switch ($updateType) {
            case Listing::TYPE_OFFER:
                $processes[] = $this->updateOfferTrackings($trackingIds, $processType);
                break;
            case Listing::TYPE_PRODUCT:
                $processes[] = $this->updateProductTrackings($trackingIds, $processType);
                break;
            case Listing::TYPE_ALL:
                $processes[] = $this->updateOfferTrackings($trackingIds, $processType);
                $processes[] = $this->updateProductTrackings($trackingIds, $processType);
                break;
            default:
                Mage::throwException('Bad update type specified: ' . $updateType);
        }

        return $processes;
    }
}
