<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Search for store ids used by the connections listings in order to init the store id of the connections
$connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();
foreach ($connections as $connection) {
    $storeIds = array(); // Save store ids used by the connection listings
    $listings = Mage::helper('mirakl_seller/connection')->getActiveListings($connection);
    /** @var MiraklSeller_Core_Model_Listing $listing */
    foreach ($listings as $listing) {
        $listingStoreId = $listing->getData('store_id');
        if (!isset($storeIds[$listingStoreId])) {
            $storeIds[$listingStoreId] = 0;
        }

        $storeIds[$listingStoreId]++; // Increment store id count
    }

    if (!empty($storeIds)) {
        // Associate the most used store id by the listings to the connection
        $connection->setStoreId(array_search(max($storeIds), $storeIds));
        $connection->save();
    }
}

// Remove the store_id column that has been moved to the mirakl_seller_connection table
$this->getConnection()->dropColumn($this->getTable('mirakl_seller/listing'), 'store_id');

$this->endSetup();
