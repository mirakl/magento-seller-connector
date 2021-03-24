<?php
/**
 * @var Mage_Sales_Model_Resource_Setup $this
 */
$this->startSetup();

$attributes = array(
    'order' => array(
        'mirakl_connection_id' => array(
            'type'     => Varien_Db_Ddl_Table::TYPE_SMALLINT,
            'unsigned' => true,
            'grid'     => true,
        ),
        'mirakl_order_id' => array(
            'type'   => Varien_Db_Ddl_Table::TYPE_VARCHAR,
            'length' => 255,
        ),
    ),
);

foreach ($attributes as $entityType => $attrCodes) {
    foreach ($attrCodes as $code => $attr) {
        $attr['visible'] = false;
        $this->addAttribute($entityType, $code, $attr);
    }
}

$this->endSetup();
