<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add Magento tier prices selection fields column to store them on connection
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'magento_tier_prices_apply_on',
    "VARCHAR(18) NOT NULL DEFAULT 'VOLUME_PRICING' COMMENT 'Choose how you would like Magento tier prices to be exported'"
);

$this->endSetup();
