<?php

use MiraklSeller_Core_Model_Listing as Listing;
use MiraklSeller_Core_Model_Offer as Offer;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Helper_Listing_Process extends MiraklSeller_Core_Helper_Data
{
    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * @var MiraklSeller_Core_Model_Offer_Loader
     */
    protected $_offerLoader;

    /**
     * @var MiraklSeller_Core_Helper_Listing_Product
     */
    protected $_productHelper;

    /**
     * @var MiraklSeller_Core_Helper_Tracking_Product
     */
    protected $_productTrackingHelper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_offerResource         = Mage::getResourceModel('mirakl_seller/offer');
        $this->_offerLoader           = Mage::getSingleton('mirakl_seller/offer_loader');
        $this->_productHelper         = Mage::helper('mirakl_seller/listing_product');
        $this->_productTrackingHelper = Mage::helper('mirakl_seller/tracking_product');
    }

    /**
     * @param   Process $process
     * @param   int     $listingId
     */
    public function refresh(Process $process, $listingId)
    {
        /** @var MiraklSeller_Core_Model_Listing $listing */
        $listing = Mage::getModel('mirakl_seller/listing')->load($listingId);

        $process->output($this->__('Refreshing products of listing #%s ...', $listing->getId()), true);

        // Retrieve listing's product ids in order to filter collection
        $productIds = $listing->build();

        $process->output($this->__('Found %d product(s) matching listing conditions', count($productIds)));

        $process->output($this->__('Updating products and offers ...'));
        $this->_offerLoader->load($listing->getId(), $productIds);

        $process->output($this->__('Done!'));
    }

    /**
     * @param   Process $process
     * @param   int     $listingId
     * @param   bool    $full
     * @param   bool    $createTracking
     * @param   array   $productIds
     */
    public function exportOffer(
        Process $process,
        $listingId,
        $full = true,
        $createTracking = true,
        array $productIds = array()
    ) {
        /** @var MiraklSeller_Core_Model_Listing $listing */
        $listing = Mage::getModel('mirakl_seller/listing')->load($listingId);
        $listing->validate();

        // Retrieve product ids associated with the listing and with offer import status set to one of:
        // NEW, SUCCESS, ERROR, DELETE
        $offerStatuses = array(
            Offer::OFFER_NEW, Offer::OFFER_SUCCESS, Offer::OFFER_ERROR, Offer::OFFER_DELETE
        );
        $where = array('offer_import_status IN (?)' => $offerStatuses);

        if (!empty($productIds)) {
            // Retrieve only products specified
            $where['product_id IN (?)'] = $productIds;
        }
        $cols = array('product_id', 'id', 'offer_hash');
        $updateProducts = $this->_offerResource->getListingProducts($listing->getId(), $where, $cols);

        if (empty($updateProducts)) {
            $process->output($this->__('No offer to export'));
            return;
        }

        // Filter listing product ids with only offer with status NEW, SUCCESS, ERROR or DELETE
        $listing->setProductIds(array_keys($updateProducts));

        // Retrieve offers data to export
        $process->output($this->__('Exporting offers of listing #%s ...', $listing->getId()), true);

        $data = Mage::getModel('mirakl_seller/listing_export_offers')->export($listing);

        $process->output($this->__('  => Found %d product(s) to export', count($data)));

        // Calculate hashes of offers data to import only modified ones later if in delta mode
        foreach ($data as $productId => $values) {
            $hash = sha1(json_encode($values));

            if ($full || $updateProducts[$productId]['offer_hash'] !== $hash) {
                // Update hash if full import mode or if offer's hash has changed
                $updateProducts[$productId]['offer_hash'] = $hash;
            } else {
                // We are doing a delta update so we remove products from being imported in Mirakl if hash did not change
                unset($updateProducts[$productId]);
                unset($data[$productId]);
            }
        }

        if (!$full) {
            $process->output($this->__('Only modified offers will be imported in Mirakl ...'));
            $process->output($this->__('  => Found %d product(s) available for export', count($updateProducts)));
        }

        if (empty($data)) {
            $process->output($this->__('No offer to export'));
            return;
        }

        // Export data to Mirakl through API OF01
        $process->output($this->__('Sending file to Mirakl through API OF01 ...'));
        $result = Mage::helper('mirakl_seller_api/offer')->importOffers($listing->getConnection(), $data);

        // Update hash of imported offers in db
        $this->_offerResource->updateMultiple($updateProducts);

        // Set offers status to PENDING for exported product ids and import tracking id
        $exportedProductIds = array_keys($data);
        // Remove offers with status DELETE
        $this->_offerResource->deleteListingOffers($listing->getId(), array(Offer::OFFER_DELETE));
        $this->_offerResource->markOffersAsPending($listing->getId(), $exportedProductIds, $result->getImportId());

        // Update listing last export date
        $listing->setLastExportDate(Varien_Date::now());
        $listing->save();

        // Create a tracking if needed
        if ($createTracking) {
            /** @var MiraklSeller_Core_Model_Listing_Tracking_Offer $tracking */
            $tracking = Mage::getModel('mirakl_seller/listing_tracking_offer');
            $tracking->setListingId($listing->getId())
                ->setImportId($result->getImportId())
                ->save();
            $process->output($this->__('New prices & stocks tracking created (id: %s)', $tracking->getId()));
        }

        $process->output($this->__('Done!'));
    }

    /**
     * @param   Process $process
     * @param   int     $listingId
     * @param   string  $productMode
     * @param   bool    $createTracking
     */
    public function exportProduct(
        Process $process,
        $listingId,
        $productMode = Listing::PRODUCT_MODE_PENDING,
        $createTracking = true
    ) {
        /** @var MiraklSeller_Core_Model_Listing $listing */
        $listing = Mage::getModel('mirakl_seller/listing')->load($listingId);
        $listing->validate();

        if ($productMode == Listing::PRODUCT_MODE_ALL) {
            $productStatus = Offer::getProductStatuses();
        } elseif ($productMode == Listing::PRODUCT_MODE_ERROR) {
            $productStatus = Offer::getProductImportFailedStatuses();
        } else {
            // Process expired products with Magento configuration nb_days_expired
            $expiredProducts = $this->_productTrackingHelper->processExpiredProducts($listingId);
            $process->output($this->__('Expiring products of listing #%s ... %s expired products', $listing->getId(), $expiredProducts));

            // Process failed products with Magento configuration nb_days_keep_failed_products
            $nbFailedProductsUpdated = $this->_productHelper->processFailedProducts($listing);
            $process->output(
                $this->__(
                    'Marking failed products of listing #%s as "to export" (failure period expired) ... %s product(s) updated',
                    $listing->getId(),
                    $nbFailedProductsUpdated
                )
            );

            $productStatus = array(Offer::PRODUCT_NEW);
        }

        // Retrieve product ids associated with the listing and with product import status set to NEW
        $productIds = $this->_offerResource->getListingProductIds($listing->getId(), null, $productStatus);

        if (empty($productIds)) {
            $process->output($this->__('No product to export'));
            return;
        }

        // Filter listing products
        $listing->setProductIds($productIds);

        // Retrieve offers and products data to export
        $process->output($this->__('Exporting products of listing #%s ...', $listing->getId()), true);

        $data = Mage::getModel('mirakl_seller/listing_export_products')->export($listing);

        if (empty($data)) {
            $process->output($this->__('No product to export'));
            return;
        }

        $process->output($this->__('  => Found %d product(s) to export', count($data)));

        // Export data to Mirakl through API P41
        $process->output($this->__('Sending file to Mirakl through API P41 ...'));
        $result = Mage::helper('mirakl_seller_api/product')->importProducts($listing->getConnection(), $data);

        // Set offers and products status to PENDING for exported product ids and import tracking id
        $exportedProductIds = array_keys($data);
        $this->_offerResource->markProductsAsPending($listing->getId(), $exportedProductIds, $result->getImportId());

        // Update listing last export date
        $listing->setLastExportDate(Varien_Date::now());
        $listing->save();

        // Create a tracking if needed
        if ($createTracking) {
            /** @var MiraklSeller_Core_Model_Listing_Tracking_Product $tracking */
            $tracking = Mage::getModel('mirakl_seller/listing_tracking_product');
            $tracking->setListingId($listing->getId())
                ->setImportId($result->getImportId())
                ->save();
            $process->output($this->__('New products tracking created (id: %s)', $tracking->getId()));
        }

        $process->output($this->__('Done!'));
    }
}
