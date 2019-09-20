<?php

class MiraklSeller_Core_Model_Offer_Loader
{
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
     * Load product ids into offers table for a specific listing
     *
     * @param   int     $listingId
     * @param   array   $productIds
     */
    public function load($listingId, array $productIds)
    {
        // Retrieve listing's existing product ids
        $existingProductIds = $this->_offerResource->getListingProductIds($listingId);

        // 1. Mark as DELETE existing products not present anymore in listing
        if (!empty($existingProductIds)) {
            $deleteProductIds = array_diff($existingProductIds, $productIds);
            if (!empty($deleteProductIds)) {
                $this->_offerResource->markOffersAsDelete($listingId, $deleteProductIds);
            }
        }

        // 2. Insert and mark as NEW products that do not already have offers
        $newProductIds = array_diff($productIds, $existingProductIds);
        if (!empty($newProductIds)) {
            $this->_offerResource->createOffers($listingId, $newProductIds);
        }

        // 3. Mark as NEW existing offers that were in error or marked as delete and that are still present in listing
        $offerStatuses = array(
            MiraklSeller_Core_Model_Offer::OFFER_ERROR,
            MiraklSeller_Core_Model_Offer::OFFER_DELETE,
        );
        $existingProductIds = $this->_offerResource->getListingProductIds($listingId, $offerStatuses);
        $updateProductIds = array_intersect($existingProductIds, $productIds);
        if (!empty($updateProductIds)) {
            $this->_offerResource->markOffersAsNew($listingId, $updateProductIds);
        }

        // 4. Mark as NEW existing products that were in error and that are still present in listing
        $productErrorStatuses = MiraklSeller_Core_Model_Offer::getProductErrorStatuses();
        $existingProductIdsInError = $this->_offerResource->getListingProductIds(
            $listingId, null, $productErrorStatuses
        );
        $updateProductIds = array_intersect($existingProductIdsInError, $productIds);
        if (!empty($updateProductIds)) {
            $this->_offerResource->markProductsAsNew($listingId, $updateProductIds);
        }
    }
}