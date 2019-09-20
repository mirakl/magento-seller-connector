<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add offer additional fields values column in order to use them during listing offer exports
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller/listing'),
    'offer_additional_fields_values',
    "MEDIUMTEXT CHARACTER SET utf8 NOT NULL COMMENT 'Offer Additional Fields Values'"
);

$this->endSetup();
