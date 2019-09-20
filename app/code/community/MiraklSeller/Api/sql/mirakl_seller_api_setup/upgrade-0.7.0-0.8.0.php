<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add SKU code column to store the name of the attribute where the operator stores the shop SKU for success report
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'success_sku_code',
    "VARCHAR(255) NULL COMMENT 'Success SKU Code' AFTER `errors_code`"
);

// Add messages code column to store the message column's code of success report
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'messages_code',
    "VARCHAR(255) NULL COMMENT 'Messages Code' AFTER `success_sku_code`"
);

$this->endSetup();