<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'carriers_mapping',
    "TEXT NOT NULL DEFAULT '' COMMENT 'Carriers Mapping Attribute' AFTER `exportable_attributes`"
);

$this->endSetup();