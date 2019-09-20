<?php

use MiraklSeller_Core_Model_Offer as Offer;
use MiraklSeller_Core_Model_Listing_Tracking_Product as Tracking;
use MiraklSeller_Core_Model_Listing_Tracking_Status_Product as ProductStatus;

class MiraklSeller_Core_Helper_Tracking_Product extends MiraklSeller_Core_Helper_Data
{
    use MiraklSeller_Core_Trait_Csv;

    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * @var MiraklSeller_Core_Helper_Listing_Product
     */
    protected $_productHelper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_offerResource = Mage::getResourceModel('mirakl_seller/offer');
        $this->_productHelper = Mage::helper('mirakl_seller/listing_product');
    }

    /**
     * @param   Tracking    $tracking
     * @param   array       $withStatus
     * @param   string      $setDefaultStatus
     * @param   string      $setDefaultMessage
     * @return  array
     */
    protected function _getTrackingProducts(
        Tracking $tracking,
        $withStatus = array(Offer::PRODUCT_PENDING),
        $setDefaultStatus = Offer::PRODUCT_WAITING_INTEGRATION,
        $setDefaultMessage = ''
    ) {
        $products = $this->_offerResource->getListingPendingProducts(
            $tracking->getListingId(),
            $tracking->getImportId(),
            array('product_id', 'id', 'product_import_status', 'product_import_message'),
            $withStatus
        );

        $now = Varien_Date::now();
        foreach ($products as &$data) {
            $data['updated_at'] = $now;
            if ($setDefaultStatus) {
                $data['product_import_status'] = $setDefaultStatus;
            }

            if ($setDefaultMessage) {
                $data['product_import_message'] = $setDefaultMessage;
            }
        }

        unset($data);

        return $products;
    }

    /**
     * @return  string
     */
    public function getInvalidReportFormatMessage()
    {
        return
            'Marketplace product reports cannot be processed. ' .
            'Download the report manually and verify your marketplace report configuration in the connection page. ' .
            'To export the product again, mark it as "to export".';
    }

    /**
     * @return  string
     */
    public function getNotFoundInReportMessage()
    {
        return
            'Product not found in marketplace product reports. ' .
            'Try to export prices & stocks. ' .
            'If the error "product not found" is returned, try to contact the marketplace. ' .
            'To export the product again, mark it as "to export".';
    }

    /**
     * @param   Tracking    $tracking
     * @param   string      $importStatus
     * @return  int
     */
    public function updateProductStatusFromImportStatus(Tracking $tracking, $importStatus)
    {
        $errorMsg = '';

        switch ($importStatus) {
            case ProductStatus::SENT:
                $finalStatus = Offer::PRODUCT_WAITING_INTEGRATION;
                break;
            case ProductStatus::COMPLETE:
                $finalStatus = Offer::PRODUCT_NOT_FOUND_IN_REPORT;
                $errorMsg = $this->__($this->getNotFoundInReportMessage());
                break;
            case ProductStatus::CANCELLED:
            case ProductStatus::EXPIRED:
            case ProductStatus::FAILED:
                $finalStatus = Offer::PRODUCT_INTEGRATION_ERROR;
                $errorMsg = $this->__($this->getNotFoundInReportMessage());
                break;
            default:
                return 0;
        }

        // Update only products that are in PENDING or WAITING_INTEGRATION status for this tracking product import id
        $where = array(
            'listing_id'                   => $tracking->getListingId(),
            'product_import_id'            => $tracking->getImportId(),
            'product_import_status IN (?)' => array(Offer::PRODUCT_PENDING, Offer::PRODUCT_WAITING_INTEGRATION),
        );

        // Initialize data to update
        $data = array(
            'updated_at'             => Varien_Date::now(),
            'product_import_status'  => $finalStatus,
            'product_import_message' => $errorMsg,
        );

        return $this->_offerResource->update($data, $where);
    }

    /**
     * Returns number of updated products according to product integration error report from P44
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function processIntegrationErrorReport(Tracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getIntegrationErrorReport());

        return $this->processIntegrationErrorReportFile($file, $tracking);
    }

    /**
     * Process product integration error report file from P44 and returns number of updated products
     *
     * @param   SplFileObject   $file
     * @param   Tracking        $tracking
     * @return  int
     */
    public function processIntegrationErrorReportFile(SplFileObject $file, Tracking $tracking)
    {
        return $this->processIntegrationReportFile($file, $tracking, Offer::PRODUCT_INTEGRATION_ERROR);
    }

    /**
     * Returns number of updated products according to new product report from P45
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function processIntegrationSuccessReport(Tracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getIntegrationSuccessReport());

        return $this->processIntegrationReportFile($file, $tracking, Offer::PRODUCT_INTEGRATION_COMPLETE);
    }

    /**
     * Returns number of updated products
     *
     * @param   SplFileObject   $file
     * @param   Tracking        $tracking
     * @param   string          $finalStatus
     * @return  int
     */
    public function processIntegrationReportFile(SplFileObject $file, Tracking $tracking, $finalStatus)
    {
        $integrationPendingStatuses = array(
            Offer::PRODUCT_PENDING,
            Offer::PRODUCT_WAITING_INTEGRATION,
            Offer::PRODUCT_INVALID_REPORT_FORMAT,
            Offer::PRODUCT_NOT_FOUND_IN_REPORT,
        );

        // Retrieve pending products data associated with the tracking product import id
        $products = $this->_getTrackingProducts(
            $tracking,
            $integrationPendingStatuses,
            Offer::PRODUCT_WAITING_INTEGRATION
        );

        // No product to process
        if (empty($products)) {
            return 0;
        }

        $listing    = $tracking->getListing();
        $connection = $listing->getConnection();
        $productIds = array_keys($products);

        // Check report validity
        $file->rewind();
        $cols = $file->fgetcsv();

        if ($finalStatus === Offer::PRODUCT_INTEGRATION_ERROR) {
            $shopSkuColumn = $connection->getSkuCode();
            $errorsColumn  = $connection->getErrorsCode();
        } else {
            $shopSkuColumn = $connection->getSuccessSkuCode();
            $errorsColumn  = $connection->getMessagesCode();
        }

        // If integration report is not valid, mark all products as INVALID_REPORT_FORMAT and quit
        if (!$this->isCsvFileValid($file) || !in_array($shopSkuColumn, $cols)) {
            return $this->_offerResource->updateProducts(
                $listing->getId(), $productIds, array(
                    'product_import_status'  => Offer::PRODUCT_INVALID_REPORT_FORMAT,
                    'product_import_message' => $this->__($this->getInvalidReportFormatMessage()),
                )
            );
        }

        $productIdsBySkus = $this->_productHelper->getProductIdsBySkus($listing, $productIds);

        // Loop on CSV file
        $file->rewind();
        $file->fgetcsv(); // Ignore first line that contains column names
        while ($row = $file->fgetcsv()) {
            $data = array_combine($cols, $row);

            $productSku = $data[$shopSkuColumn];

            if (!isset($productIdsBySkus[$productSku])) {
                continue;
            }

            $productId = $productIdsBySkus[$productSku];
            if (isset($products[$productId])) {
                $errorMessage = isset($data[$errorsColumn]) ? trim($data[$errorsColumn]) : '';
                $products[$productId]['product_import_status']  = $finalStatus;
                $products[$productId]['product_import_message'] = $errorMessage;
            }
        }

        // Update all offers in an unique query
        return $this->_offerResource->updateMultiple($products);
    }

    /**
     * Updates product status to SUCCESS if offer status is SUCCESS.
     * Returns number of updated products.
     *
     * @param   MiraklSeller_Core_Model_Listing_Tracking_Offer  $tracking
     * @return  int
     */
    public function updateProductStatusFromOffer(MiraklSeller_Core_Model_Listing_Tracking_Offer $tracking)
    {
        // Update ALL products for this tracking product import id where import status is SUCCESS
        $where = array(
            'listing_id'                 => $tracking->getListingId(),
            'offer_import_id'            => $tracking->getImportId(),
            'product_import_status != ?' => Offer::PRODUCT_SUCCESS,
            'offer_import_status = ?'    => Offer::OFFER_SUCCESS,
        );

        // Initialize data to update
        $data = array(
            'updated_at'             => Varien_Date::now(),
            'product_import_status'  => Offer::PRODUCT_SUCCESS,
            'product_import_message' => '',
        );

        // If offer status is SUCCESS in Mirakl, it means that product has been imported successfully
        $updated = $this->_offerResource->update($data, $where);

        return $updated;
    }

    /**
     * Returns number of updated products according to error report from P47
     *
     * @param   Tracking    $tracking
     * @return  int
     */
    public function processTransformationErrorReport(Tracking $tracking)
    {
        // Create a temp file in order to parse CSV data easily
        $file = $this->createCsvFileFromString($tracking->getTransformationErrorReport());

        return $this->processTransformationErrorReportFile($file, $tracking);
    }

    /**
     * Returns number of updated products according to error report file (P47)
     *
     * @param   SplFileObject   $file
     * @param   Tracking        $tracking
     * @return  int
     */
    public function processTransformationErrorReportFile(SplFileObject $file, Tracking $tracking)
    {
        // Retrieve pending products data associated with the tracking product import id
        $products = $this->_getTrackingProducts($tracking);

        // Loop on CSV file
        $cols = $file->fgetcsv();
        while ($row = $file->fgetcsv()) {
            $data = array_combine($cols, $row);
            $productId = $data['entity_id'];
            if (isset($products[$productId])) {
                $warnings = !empty($data['warnings']) ? $data['warnings'] : '';
                $errors = !empty($data['errors']) ? $data['errors'] : '';
                if (!empty($errors)) {
                    $products[$productId]['product_import_status'] = Offer::PRODUCT_TRANSFORMATION_ERROR;
                }

                $products[$productId]['product_import_message'] = trim($warnings . "\n" . $errors);
            }
        }

        // Update all offers in an unique query
        return $this->_offerResource->updateMultiple($products);
    }

    /**
     * Process expired products with diff between (now() - nb days in Magento configuration) and last tracking product creation date
     *   -> Change product statuses from "Waiting for integration" to "Waiting for export"
     *   -> Change last tracking product status to "Integration expired"
     *
     * @param   int             $listingId
     * @param   Tracking|null   $lastTrackingProduct
     * @return  int
     */
    public function processExpiredProducts($listingId, Tracking $lastTrackingProduct = null)
    {
        $nbUpdatedProducts = 0;

        if (empty($lastTrackingProduct)) {
            // Retrieve last product tracking
            $lastTrackingProduct = Mage::getModel('mirakl_seller/listing_tracking_product')
                ->getCollection()
                ->getLastProductTrackingForListing($listingId)
                ->getFirstItem();
        }

        if ($lastTrackingProduct->getId()) {
            $today = new \DateTime();
            $interval = sprintf('P%dD', Mage::helper('mirakl_seller/config')->getNbDaysExpired());
            $compareDate = $today->sub(new \DateInterval($interval));

            if (new \DateTime($lastTrackingProduct->getCreatedAt()) < $compareDate) {
                $waitingProductIds = $this->_offerResource->getListingProductIds(
                    $listingId, null, array(Offer::PRODUCT_WAITING_INTEGRATION)
                );

                if (is_array($waitingProductIds) && !empty($waitingProductIds)) {
                    // Update offers with status Waiting for export
                    $nbUpdatedProducts = $this->_offerResource->markProductsAsNew($listingId, $waitingProductIds);

                    if ($nbUpdatedProducts > 0) {
                        $lastTrackingProduct->setImportStatus(ProductStatus::EXPIRED);
                        $lastTrackingProduct->save();
                    }
                }
            }
        }

        return $nbUpdatedProducts;
    }
}
