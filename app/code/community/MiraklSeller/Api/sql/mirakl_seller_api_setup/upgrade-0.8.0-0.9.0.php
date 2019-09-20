<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

// Add the last synchronization date of Mirakl orders
$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller_api/connection'),
    'last_orders_synchronization_date',
    "DATETIME NULL COMMENT 'Last Orders Synchronization Date'"
);

// Set current date to existing connections for last Mirakl orders synchronization date
$now = Varien_Date::now();
$query = <<<SQL
UPDATE `{$this->getTable('mirakl_seller_api/connection')}` SET `last_orders_synchronization_date` = '{$now}'
SQL;

$this->getConnection()->query($query);

$this->endSetup();