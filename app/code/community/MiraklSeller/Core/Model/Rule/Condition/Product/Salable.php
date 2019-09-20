<?php

class MiraklSeller_Core_Model_Rule_Condition_Product_Salable
    extends MiraklSeller_Core_Model_Rule_Condition_Product_Boolean
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('product_is_salable');
        $this->setValueName('Is Salable');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName()
    {
        return Mage::helper('mirakl_seller')->__('Is Salable');
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOperatorOptions()
    {
        return array('==' => Mage::helper('rule')->__('is'));
    }

    /**
     * {@inheritdoc}
     */
    public function collectValidatedAttributes($productCollection)
    {
        /** @var $adapter Varien_Db_Adapter_Pdo_Mysql */
        $adapter = Mage::getResourceSingleton('core/resource')->getReadConnection();
        $select = $adapter->select()
            ->from(Mage::getResourceSingleton('cataloginventory/stock_status')->getMainTable(), 'product_id')
            ->where('stock_status = 1');

        $this->_entityAttributeValues = array_flip($adapter->fetchCol($select));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Varien_Object $object)
    {
        $isSalable = isset($this->_entityAttributeValues[$object->_getData('entity_id')]);

        return $this->getValue() ? $isSalable : !$isSalable;
    }
}