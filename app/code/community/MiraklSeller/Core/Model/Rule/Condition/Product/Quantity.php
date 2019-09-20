<?php

class MiraklSeller_Core_Model_Rule_Condition_Product_Quantity
    extends MiraklSeller_Core_Model_Rule_Condition_Product
{
    protected $_inputType = 'numeric';

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('product_quantity');
        $this->setValueName('Quantity In Stock');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName()
    {
        return Mage::helper('mirakl_seller')->__('Quantity In Stock');
    }

    /**
     * {@inheritdoc}
     */
    public function collectValidatedAttributes($productCollection)
    {
        /** @var $adapter Varien_Db_Adapter_Pdo_Mysql */
        $adapter = Mage::getResourceSingleton('core/resource')->getReadConnection();
        /** @var $collection Mage_CatalogInventory_Model_Resource_Stock_Item_Collection */
        $collection = Mage::getResourceModel('cataloginventory/stock_item_collection');
        $select = $collection->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(array('product_id', 'qty'));

        $this->_entityAttributeValues = array_map('floatval', $adapter->fetchPairs($select));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Varien_Object $object)
    {
        if (isset($this->_entityAttributeValues[$object->_getData('entity_id')])) {
            $object->setData($this->_getData('attribute'), $this->_entityAttributeValues[$object->_getData('entity_id')]);
        }

        return $this->_validateProduct($object);
    }
}