<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

/**
 * Create table for entity 'mirakl_seller/offer'
 */
$this->getConnection()->dropTable($this->getTable('mirakl_seller/offer'));
$table = $this->getConnection()
    ->newTable($this->getTable('mirakl_seller/offer'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'Magento Offer Id')
    ->addColumn('listing_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false, 'default' => '0',), 'Mirakl Listing ID')
    ->addColumn('product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false, 'default' => '0',), 'Magento Product ID')
    ->addColumn('product_import_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => true, 'default' => null), 'Product Import Id')
    ->addColumn('product_import_status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable' => true, 'default' => null), 'Product Import Status')
    ->addColumn('product_import_message', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array('nullable' => true, 'default' => null), 'Product Import Message')
    ->addColumn('offer_import_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => true, 'default' => null), 'Offer Import Id')
    ->addColumn('offer_import_status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable' => false), 'Offer Import Status')
    ->addColumn('offer_error_message', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array('nullable' => true, 'default' => null), 'Offer Error Message')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true, 'default' => null), 'Created Date')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true, 'default' => null), 'Updated Date')
    ->addIndex($this->getIdxName('mirakl_seller/offer', array('listing_id')), array('listing_id'))
    ->addIndex($this->getIdxName('mirakl_seller/offer', array('product_id')), array('product_id'))
    ->addIndex($this->getIdxName('mirakl_seller/offer', array('product_import_id')), array('product_import_id'))
    ->addIndex($this->getIdxName('mirakl_seller/offer', array('product_import_status')), array('product_import_status'))
    ->addIndex($this->getIdxName('mirakl_seller/offer', array('offer_import_id')), array('offer_import_id'))
    ->addIndex($this->getIdxName('mirakl_seller/offer', array('offer_import_status')), array('offer_import_status'))
    ->addForeignKey(
        $this->getFkName('mirakl_seller/offer', 'listing_id', 'mirakl_seller/listing', 'id'),
        'listing_id', $this->getTable('mirakl_seller/listing'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->addForeignKey(
        $this->getFkName('mirakl_seller/offer', 'product_id', 'catalog/product', 'entity_id'),
        'product_id', $this->getTable('catalog/product'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Mirakl Offer');
$this->getConnection()->createTable($table);

$this->endSetup();
