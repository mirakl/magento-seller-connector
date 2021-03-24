<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $this
 */
$this->startSetup();

$attributes = array(
    'shipment' => array(
        'mirakl_shipment_id' => array(
            'type' => Varien_Db_Ddl_Table::TYPE_VARCHAR,
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
    $this->getTable('sales/shipment'),
    $this->getIdxName('sales/shipment', array('mirakl_shipment_id')),
    array('mirakl_shipment_id')
);

$this->endSetup();
