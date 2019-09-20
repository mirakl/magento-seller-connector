<?php
/**
 * @var Mage_Core_Model_Resource_Setup $this
 */
$this->startSetup();

/**
 * Create table for entity 'mirakl_seller_process/process'
 */
$this->getConnection()->dropTable($this->getTable('mirakl_seller_process/process'));
$table = $this->getConnection()
    ->newTable($this->getTable('mirakl_seller_process/process'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'Process Id')
    ->addColumn('parent_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => true, 'default' => null), 'Parent Id')
    ->addColumn('type', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('nullable' => false), 'Type')
    ->addColumn('name', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => false), 'Name')
    ->addColumn('status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable' => false, 'default' => 'pending'), 'Status')
    ->addColumn('mirakl_status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('default' => null), 'Mirakl Status')
    ->addColumn('synchro_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'default' => null), 'Synchro Id')
    ->addColumn('output', Varien_Db_Ddl_Table::TYPE_TEXT, '1g', array('default' => null, 'nullable' => true), 'Output')
    ->addColumn('duration', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('default' => null, 'nullable' => true), 'Duration')
    ->addColumn('file', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array('default' => null), 'File')
    ->addColumn('mirakl_file', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array('default' => null), 'Mirakl File')
    ->addColumn('helper', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('default' => null), 'Helper')
    ->addColumn('method', Varien_Db_Ddl_Table::TYPE_TEXT, 100, array('default' => null), 'Method')
    ->addColumn('params', Varien_Db_Ddl_Table::TYPE_TEXT, '2M', array('default' => null), 'Parameters')
    ->addColumn('hash', Varien_Db_Ddl_Table::TYPE_TEXT, 32, array('default' => null), 'Hash')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('default' => null, 'nullable' => true), 'Created At')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('default' => null, 'nullable' => true), 'Updated At')
    ->addIndex($this->getIdxName('mirakl_seller_process/process', array('type')), array('type'))
    ->addIndex($this->getIdxName('mirakl_seller_process/process', array('status')), array('status'))
    ->addIndex($this->getIdxName('mirakl_seller_process/process', array('mirakl_status')), array('mirakl_status'))
    ->addIndex($this->getIdxName('mirakl_seller_process/process', array('hash')), array('hash'))
    ->addForeignKey(
        $this->getFkName('mirakl_seller_process/process', 'parent_id', 'mirakl_seller_process/process', 'id'),
        'parent_id', $this->getTable('mirakl_seller_process/process'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Mirakl Processes');
$this->getConnection()->createTable($table);

$this->endSetup();
