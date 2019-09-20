<?php

use Mirakl\MMP\OperatorShop\Domain\Offer\Importer\ImportStatus;
use MiraklSeller_Core_Model_Listing_Tracking_Status_Product as ProductStatus;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Helper_Tracking_Process extends MiraklSeller_Core_Helper_Data
{
    /**
     * @var MiraklSeller_Core_Helper_Tracking_Api
     */
    protected $_apiHelper;

    /**
     * @var MiraklSeller_Core_Helper_Tracking_Offer
     */
    protected $_offerHelper;

    /**
     * @var MiraklSeller_Core_Helper_Tracking_Product
     */
    protected $_productHelper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_apiHelper     = Mage::helper('mirakl_seller/tracking_api');
        $this->_offerHelper   = Mage::helper('mirakl_seller/tracking_offer');
        $this->_productHelper = Mage::helper('mirakl_seller/tracking_product');
    }

    /**
     * @param   Process $process
     * @param   array   $trackingIds
     */
    public function updateOffersImportStatus(Process $process, $trackingIds)
    {
        /** @var MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer_Collection $collection */
        $collection = Mage::getModel('mirakl_seller/listing_tracking_offer')->getCollection();
        $collection->addIdFilter($trackingIds)
            ->addExcludeOfferStatusCompleteFilter()
            ->addWithImportIdFilter();

        if (!$collection->count()) {
            $process->output($this->__('No available tracking to update'));

            return;
        }

        $process->output($this->__('Found %d tracking(s) to update', $collection->count()));

        foreach ($collection as $tracking) {
            $process->output($this->__('Getting import status of tracking #%s ...', $tracking->getId()));

            try {
                // Call API OF02 and save offer import status
                $result = $this->_apiHelper->updateOfferTrackingStatus($tracking);

                // Output API result, might be useful
                $process->output(json_encode($result->toArray(), JSON_PRETTY_PRINT));

                // Check for error report
                if ($result->hasErrorReport()) {
                    $process->output($this->__('Downloading error report ...', $tracking->getId()));

                    // Call API OF03 and save offer import error report
                    $this->_apiHelper->updateOfferErrorReport($tracking);
                    $process->output($this->__('Error report saved'));

                    // Update offers status according to error report
                    $process->output($this->__('Updating offers status according to error report ...'));
                    $updatedOffersCount = $this->_offerHelper->processErrorReport($tracking);
                    $process->output($this->__('Updated %d prices & stocks', $updatedOffersCount));
                } elseif ($result->getStatus() == ImportStatus::COMPLETE) {
                    // If import is complete and no error report is present, mark all offers as SUCCESS
                    $updatedOffersCount = $this->_offerHelper->markAsSuccess($tracking);
                    $process->output($this->__('Updated %d prices & stocks', $updatedOffersCount));
                }

                if ($result->getStatus() == ImportStatus::COMPLETE) {
                    $updatedProductsCount = $this->_productHelper->updateProductStatusFromOffer($tracking);
                    $process->output($this->__('Updated %d products', $updatedProductsCount));
                }

                $process->output($this->__('Tracking #%s updated!', $tracking->getId()));
            } catch (\Exception $e) {
                // Do not stop process execution if an error occurred, continue with next tracking
                $process->output($this->__('ERROR: %s', $e->getMessage()));
            }
        }
    }

    /**
     * @param   Process $process
     * @param   array   $trackingIds
     */
    public function updateProductsImportStatus(Process $process, $trackingIds)
    {
        /** @var MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection $collection */
        $collection = Mage::getModel('mirakl_seller/listing_tracking_product')->getCollection();
        $collection->addIdFilter($trackingIds)
            ->addExcludeProductStatusFinalFilter()
            ->addWithImportIdFilter();

        if (!$collection->count()) {
            $process->output($this->__('No available tracking to update'));

            return;
        }

        $process->output($this->__('Found %d tracking(s) to update', $collection->count()));

        foreach ($collection as $tracking) {
            $listing = $tracking->getListing();
            $connection = $listing->getConnection();

            $process->output($this->__('Getting import status of tracking #%s ...', $tracking->getId()));

            try {
                // Call API P42 and save product import status
                $result = $this->_apiHelper->updateProductTrackingStatus($tracking);

                // Output API result, might be useful
                $process->output(json_encode($result->toArray(), JSON_PRETTY_PRINT));

                // Check for transformation error report
                if ($result->hasTransformationErrorReport() && !$tracking->getTransformationErrorReport()) {
                    $process->output($this->__('Downloading transformation error report ...', $tracking->getId()));

                    // Call API P47 and save product transformation error report
                    $this->_apiHelper->updateProductTransformationErrorReport($tracking);

                    // Update products status according to error report
                    $process->output($this->__('Updating products status according to transformation error report ...'));
                    $updatedProductsCount = $this->_productHelper->processTransformationErrorReport($tracking);
                    $process->output($this->__('Updated %d products', $updatedProductsCount));
                }

                // Check for integration error report
                if ($result->hasErrorReport() && !$tracking->getIntegrationErrorReport()) {
                    $process->output($this->__('Downloading integration error report ...', $tracking->getId()));

                    // Call API P44 and save product integration error report
                    $this->_apiHelper->updateProductIntegrationErrorReport($tracking);

                    // Process integration error report
                    $process->output($this->__('Updating products status according to integration error report ...'));
                    $updatedProductsCount = $this->_productHelper->processIntegrationErrorReport($tracking);
                    $process->output($this->__('Updated %d products', $updatedProductsCount));
                }

                // Check for integration success report
                if ($result->hasNewProductReport() && !$tracking->getIntegrationSuccessReport()) {
                    $process->output($this->__('Downloading product success report ...', $tracking->getId()));

                    // Call API P45 and save new product integration
                    $this->_apiHelper->updateProductIntegrationSuccessReport($tracking);

                    // Process integration success report
                    $successCount = $this->_productHelper->processIntegrationSuccessReport($tracking);
                    $process->output($this->__('Updated %d products', $successCount));
                }

                // Update products status according to import status
                if (ProductStatus::isStatusComplete($result->getImportStatus())) {
                    // If product is still in PENDING status, update it according to the import status
                    $process->output($this->__('Updating products status according to import status ...'));
                    $updatedProductsCount = $this->_productHelper->updateProductStatusFromImportStatus(
                        $tracking, $result->getImportStatus()
                    );
                    $process->output($this->__('Updated %d products', $updatedProductsCount));
                }

                // Process expired product with Magento configuration nb_days_expired
                $expiredProducts = $this->_productHelper->processExpiredProducts($listing->getId(), $tracking);
                $process->output($this->__('Expiring products of listing #%s ... %s expired products', $listing->getId(), $expiredProducts));

                $process->output($this->__('Tracking #%s updated!', $tracking->getId()));
            } catch (\Exception $e) {
                // Do not stop process execution if an error occurred, continue with next tracking
                $process->output($this->__('ERROR: %s', $e->getMessage()));
            }
        }
    }
}
