<?php

use MiraklSeller_Core_Model_Listing_Tracking_Offer as OfferTracking;
use MiraklSeller_Core_Model_Listing_Tracking_Product as ProductTracking;

class MiraklSeller_Core_Helper_Tracking_Api extends MiraklSeller_Core_Helper_Data
{
    /**
     * @param   OfferTracking   $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateOfferErrorReport(OfferTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API OF03 to get import error report
        $result = Mage::helper('mirakl_seller_api/offer')
            ->getOffersImportErrorReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save import error report in tracking
        $tracking->setErrorReport($file->fread($file->fstat()['size']));
        $tracking->save();

        return $result;
    }

    /**
     * @param   OfferTracking   $tracking
     * @return  \Mirakl\MMP\OperatorShop\Domain\Offer\Importer\OfferImportResult
     */
    public function updateOfferTrackingStatus(OfferTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API OF02 to get import result
        $result = Mage::helper('mirakl_seller_api/offer')
            ->getOffersImportResult($listing->getConnection(), $tracking->getImportId());

        // Save import status in tracking
        $tracking->setImportStatus($result->getStatus());
        $tracking->save();

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateProductIntegrationErrorReport(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P44 to get integration error report
        $result = Mage::helper('mirakl_seller_api/product')
            ->getProductsIntegrationErrorReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save integration error report in tracking
        $tracking->setIntegrationErrorReport($file->fread($file->fstat()['size']));
        $tracking->save();

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateProductIntegrationSuccessReport(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P45 to get new product integration report
        $result = Mage::helper('mirakl_seller_api/product')
            ->getNewProductsIntegrationReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save integration success report in tracking
        $tracking->setIntegrationSuccessReport($file->fread($file->fstat()['size']));
        $tracking->save();

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\Core\Domain\FileWrapper
     */
    public function updateProductTransformationErrorReport(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P47 to get transformation error report
        $result = Mage::helper('mirakl_seller_api/product')
            ->getProductsTransformationErrorReport($listing->getConnection(), $tracking->getImportId());

        $file = $result->getFile();
        $file->rewind();

        // Save transformation error report in tracking
        $tracking->setTransformationErrorReport($file->fread($file->fstat()['size']));
        $tracking->save();

        return $result;
    }

    /**
     * @param   ProductTracking     $tracking
     * @return  \Mirakl\MCI\Common\Domain\Product\ProductImportResult
     */
    public function updateProductTrackingStatus(ProductTracking $tracking)
    {
        // Retrieve tracking's listing in order to get associated connection
        $listing = $tracking->getListing();

        // Call API P42 to get import result
        $result = Mage::helper('mirakl_seller_api/product')
            ->getProductImportResult($listing->getConnection(), $tracking->getImportId());

        // Save import status in tracking
        $tracking->setImportStatus($result->getImportStatus());
        $tracking->setImportStatusReason($result->getReasonStatus());
        $tracking->save();

        return $result;
    }
}
