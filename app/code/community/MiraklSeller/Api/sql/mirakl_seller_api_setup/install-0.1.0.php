<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

/**
 * Create table for entity 'mirakl_seller_api/connection'
 */
$this->getConnection()->dropTable($this->getTable('mirakl_seller_api/connection'));
$table = $this->getConnection()
    ->newTable($this->getTable('mirakl_seller_api/connection'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'Connection Id')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => false), 'Connection Name')
    ->addColumn('api_url', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => false), 'API URL')
    ->addColumn('api_key', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => false), 'API Key')
    ->addColumn('shop_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => true, 'default' => null), 'Shop Id')
    ->setComment('Mirakl Connections');
$this->getConnection()->createTable($table);

$this->endSetup();
