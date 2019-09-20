<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add Magento exportable attributes fields column to store them on connection
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'exportable_attributes',
    "MEDIUMTEXT CHARACTER SET utf8 NOT NULL COMMENT 'Exportable Attributes (associated products)'"
);

$this->endSetup();
