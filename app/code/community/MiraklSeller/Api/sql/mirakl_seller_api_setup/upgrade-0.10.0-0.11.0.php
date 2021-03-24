<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'exported_prices_attribute',
    "VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Exported Prices Attribute' AFTER `exportable_attributes`"
);

$this->endSetup();