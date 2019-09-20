<?php
/**
 * @var Mage_Catalog_Model_Resource_Setup $this
 */
$this->startSetup();

/**
 * Create table for entity 'mirakl_seller/listing_tracking_product'
 */
$this->getConnection()->dropTable($this->getTable('mirakl_seller/listing_tracking_product'));
$table = $this->getConnection()
    ->newTable($this->getTable('mirakl_seller/listing_tracking_product'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'Magento Id')
    ->addColumn('listing_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false, 'default' => '0',), 'Mirakl Listing ID')
    ->addColumn('import_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => true, 'default' => null), 'Import Id')
    ->addColumn('import_status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable' => true, 'default' => null), 'Import Status')
    ->addColumn('import_status_reason', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array('nullable' => true, 'default' => null), 'Import Status Reason')
    ->addColumn('transformation_error_report', Varien_Db_Ddl_Table::TYPE_TEXT, '4G', array('nullable' => true, 'default' => null), 'Transformation Error Report')
    ->addColumn('integration_error_report', Varien_Db_Ddl_Table::TYPE_TEXT, '4G', array('nullable' => true, 'default' => null), 'Integration Error Report')
    ->addColumn('integration_success_report', Varien_Db_Ddl_Table::TYPE_TEXT, '4G', array('nullable' => true, 'default' => null), 'Integration Success Report')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true, 'default' => null), 'Created Date')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true, 'default' => null), 'Updated Date')
    ->addIndex($this->getIdxName('mirakl_seller/listing_tracking_product', array('listing_id')), array('listing_id'))
    ->addIndex($this->getIdxName('mirakl_seller/listing_tracking_product', array('import_status')), array('import_status'))
    ->addForeignKey(
        $this->getFkName('mirakl_seller/listing_tracking_product', 'listing_id', 'mirakl_seller/listing', 'id'),
        'listing_id', $this->getTable('mirakl_seller/listing'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Mirakl Listing Tracking Product');
$this->getConnection()->createTable($table);

/**
 * Create table for entity 'mirakl_seller/listing_tracking_offer'
 */
$this->getConnection()->dropTable($this->getTable('mirakl_seller/listing_tracking_offer'));
$table = $this->getConnection()
    ->newTable($this->getTable('mirakl_seller/listing_tracking_offer'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'Magento Id')
    ->addColumn('listing_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => false, 'default' => '0',), 'Mirakl Listing ID')
    ->addColumn('import_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('unsigned' => true, 'nullable' => true, 'default' => null), 'Import Id')
    ->addColumn('import_status', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array('nullable' => true, 'default' => null), 'Import Status')
    ->addColumn('error_report', Varien_Db_Ddl_Table::TYPE_TEXT, '4G', array('nullable' => true, 'default' => null), 'Error Report')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true, 'default' => null), 'Created Date')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, array('nullable' => true, 'default' => null), 'Updated Date')
    ->addIndex($this->getIdxName('mirakl_seller/listing_tracking_offer', array('listing_id')), array('listing_id'))
    ->addIndex($this->getIdxName('mirakl_seller/listing_tracking_offer', array('import_status')), array('import_status'))
    ->addForeignKey(
        $this->getFkName('mirakl_seller/listing_tracking_offer', 'listing_id', 'mirakl_seller/listing', 'id'),
        'listing_id', $this->getTable('mirakl_seller/listing'), 'id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Mirakl Listing Tracking Offer');
$this->getConnection()->createTable($table);

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller/listing'),
    'product_id_type',
    "VARCHAR(255) NULL COMMENT 'Product Id Type' AFTER `builder_params`"
);

$this->getConnection()->addColumn(
    $this->getTable('mirakl_seller/listing'),
    'product_id_value_attribute',
    "VARCHAR(255) NULL COMMENT 'Product Id Value Attribute' AFTER `product_id_type`"
);

$this->endSetup();
