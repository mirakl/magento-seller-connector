<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller/offer'),
    'offer_hash',
    "VARCHAR(40) NULL COMMENT 'Offer Hash' AFTER `offer_error_message`"
);

// Add an index on offer_hash column to make SQL queries faster when filtering on it
$this->getConnection()->addIndex(
    $this->getTable('mirakl_seller/offer'),
    $this->getIdxName('mirakl_seller/offer', array('offer_hash')),
    array('offer_hash')
);

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller/listing'),
    'variants_attributes',
    "VARCHAR(255) NULL COMMENT 'Variants Attributes' AFTER `product_id_value_attribute`"
);
$this->endSetup();
