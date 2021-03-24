<?php

class MiraklSeller_Api_Model_Resource_Connection extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @var array
     */
    protected $_serializableFields = array(
        'exportable_attributes' => array(array(), array()),
        'carriers_mapping' => array(array(), array()),
    );

    /**
     * Initialize model and primary key field
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_api/connection', 'id');
    }

    /**
     * {@inheritdoc}
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        /** @var MiraklSeller_Api_Model_Connection $object */
        if (!$object->getShopId()) {
            $object->setShopId(null);
        }

        if (!$object->getLastOrdersSynchronizationDate() && $object->isObjectNew()) {
            $object->setLastOrdersSynchronizationDate(Varien_Date::now());
        }

        return parent::_beforeSave($object);
    }
}
