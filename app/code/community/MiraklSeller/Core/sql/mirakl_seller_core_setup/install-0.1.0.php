<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

/**
 * Create table for entity 'mirakl_seller/listing'
 */
$this->getConnection()->dropTable($this->getTable('mirakl_seller/listing'));
$table = $this->getConnection()
    ->newTable($this->getTable('mirakl_seller/listing'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'Listing Id')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => false), 'Marketplace Name')
    ->addColumn(
        'connection_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ), 'Mirakl Connection ID'
    )
    ->addColumn(
        'store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
            'unsigned'  => true,
            'nullable'  => false,
            'default'   => '0',
        ), 'Magento Store ID'
    )
    ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_BOOLEAN, null, array('default' => true, 'nullable' => false), 'Is Listing Active')
    ->addColumn('builder_model', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('default' => null), 'Builder Model')
    ->addColumn('builder_params', Varien_Db_Ddl_Table::TYPE_TEXT, '2M', array('default' => null), 'Builder Parameters')
    ->addColumn('last_export_date', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('default' => null, 'nullable' => true), 'Last Export Date')
    ->addIndex($this->getIdxName('mirakl_seller/listing', array('is_active')), array('is_active'))
    ->addIndex($this->getIdxName('mirakl_seller/listing', array('store_id')), array('store_id'))
    ->addForeignKey(
        $this->getFkName('mirakl_seller/listing', 'connection_id', 'mirakl_seller_api/connection', 'id'),
        'connection_id', $this->getTable('mirakl_seller_api/connection'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('mirakl_seller/listing', 'store_id', 'core/store', 'store_id'),
        'store_id', $this->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Mirakl Listing');
$this->getConnection()->createTable($table);

$this->endSetup();
