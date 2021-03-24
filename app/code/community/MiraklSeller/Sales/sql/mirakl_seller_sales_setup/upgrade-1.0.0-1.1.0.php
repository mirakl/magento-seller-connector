<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $this
 */
$this->startSetup();

$attributes = array(
    'creditmemo' => array(
        'mirakl_refund_id' => array(
            'type'     => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'unsigned' => true,
        ),
        'mirakl_refund_taxes' => array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        ),
        'mirakl_refund_shipping_taxes' => array(
            'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        ),
    ),
);

foreach ($attributes as $entityType => $attrCodes) {
    foreach ($attrCodes as $code => $attr) {
        $attr['visible'] = false;
        $this->addAttribute($entityType, $code, $attr);
    }
}

$this->getConnection()->addIndex(
    $this->getTable('sales/creditmemo'),
    $this->getIdxName('sales/creditmemo', array('mirakl_refund_id')),
    array('mirakl_refund_id')
);

$this->endSetup();
