<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add SKU code column to store the name of the attribute where the operator stores the shop SKU
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'sku_code',
    "VARCHAR(255) NULL COMMENT 'SKU Code' AFTER `shop_id`"
);

// Add errors code column to store the error column's code of the P44 file
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'errors_code',
    "VARCHAR(255) NULL COMMENT 'Errors Code' AFTER `sku_code`"
);

$this->endSetup();
