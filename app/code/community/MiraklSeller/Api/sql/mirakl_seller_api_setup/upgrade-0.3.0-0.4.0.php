<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add offer additional fields column to store them on connection
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'offer_additional_fields',
    "MEDIUMTEXT CHARACTER SET utf8 NOT NULL COMMENT 'Offer Additional Fields'"
);

$this->endSetup();
