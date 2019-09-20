<?php

class MiraklSeller_Core_Model_Resource_Listing extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @var array
     */
    protected $_serializableFields = array(
        'builder_params' => array(null, array()),
        'variants_attributes' => array(null, array()),
        'offer_additional_fields_values' => array(null, array()),
    );

    /**
     * Initialize model and primary key field
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller/listing', 'id');
    }
}
