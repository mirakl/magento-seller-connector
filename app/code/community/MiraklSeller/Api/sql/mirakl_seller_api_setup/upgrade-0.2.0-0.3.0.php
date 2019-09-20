<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();
$defaultDelimiter = ';';

// Add CSV delimiter column for custom export settings
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'csv_delimiter',
    "VARCHAR(5) NOT NULL DEFAULT '{$defaultDelimiter}' COMMENT 'CSV Delimiter'"
);
$this->endSetup();