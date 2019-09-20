<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'store_id',
    "SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Store ID' AFTER `api_key`"
);

$this->getConnection()->addIndex(
    $this->getTable('mirakl_seller_api/connection'),
    $this->getIdxName('mirakl_seller_api/connection', array('store_id')),
    array('store_id')
);

$this->getConnection()->addForeignKey(
    $this->getFkName('mirakl_seller_api/connection', 'store_id', 'core/store', 'store_id'),
    $this->getTable('mirakl_seller_api/connection'),
    'store_id',
    $this->getTable('core/store'),
    'store_id',
    Varien_Db_Ddl_Table::ACTION_CASCADE,
    Varien_Db_Ddl_Table::ACTION_CASCADE
);

$this->endSetup();