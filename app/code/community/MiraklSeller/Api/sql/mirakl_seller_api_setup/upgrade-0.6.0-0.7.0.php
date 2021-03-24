<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Drop CSV delimiter column for custom export settings
$this->getConnection()->dropColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'csv_delimiter'
);

$this->endSetup();