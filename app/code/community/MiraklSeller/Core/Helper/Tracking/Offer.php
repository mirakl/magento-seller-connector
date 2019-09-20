<?php

use MiraklSeller_Core_Model_Offer as Offer;
use MiraklSeller_Core_Model_Listing_Tracking_Offer as Tracking;

class MiraklSeller_Core_Helper_Tracking_Offer extends MiraklSeller_Core_Helper_Data
{
    use MiraklSeller_Core_Trait_Csv;

    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_offerResource = Mage::getResourceModel('mirakl_seller/offer');
    }

    /**
     * Mark ALL pending offers associated with the tracking offer import id as SUCCESS
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function markAsSuccess(Tracking $tracking)
    {
        // Retrieve pending offers data associated with the tracking offer import id
        $offersData = $this->_getTrackingPendingOffersData($tracking);

        // Update all offers in an unique query
        return $this->_offerResource->updateMultiple($offersData);
    }

    /**
     * Returns number of updated offers according to error report
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function processErrorReport(Tracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getErrorReport());

        return $this->processErrorReportFile($file, $tracking);
    }

    /**
     * Returns number of updated offers according to error report file
     *
     * @param   SplFileObject   $file
     * @param   Tracking        $tracking
     * @return  int
     */
    public function processErrorReportFile(SplFileObject $file, Tracking $tracking)
    {
        // Retrieve pending offers data associated with the tracking offer import id
        $offersData = $this->_getTrackingPendingOffersData($tracking);

        $file->rewind();

        // Loop on CSV file
        $cols = $file->fgetcsv();

        while ($row = $file->fgetcsv()) {
            $data = array_combine($cols, $row);
            $productId = $data['entity_id'];
            if (isset($offersData[$productId])) {
                $offersData[$productId]['offer_import_status'] = Offer::OFFER_ERROR;
                $offersData[$productId]['offer_error_message'] = $data['error-message'];
            }
        }

        // Update all offers in an unique query
        return $this->_offerResource->updateMultiple($offersData);
    }

    /**
     * Returns tracking exported offers that have status PENDING
     * (need to specify status PENDING because it may have been updated to DELETE in the meanwhile)
     *
     * @param   Tracking    $tracking
     * @return  array
     */
    protected function _getTrackingPendingOffersData(Tracking $tracking)
    {
        $offersData = $this->_offerResource->getListingPendingOffers(
            $tracking->getListingId(), $tracking->getImportId()
        );

        // No error occurred on product means offer added so define status to SUCCESS by default
        array_walk(
            $offersData, function (&$value) {
                $value['offer_import_status'] = Offer::OFFER_SUCCESS;
                $value['offer_error_message'] = null;
                $value['updated_at']          = Varien_Date::now();
            }
        );

        return $offersData;
    }
}
