<?php

class MiraklSeller_Core_Model_Rule_Condition_Product_Stock
    extends MiraklSeller_Core_Model_Rule_Condition_Product_Boolean
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('product_stock');
        $this->setValueName('In Stock');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName()
    {
        return Mage::helper('mirakl_seller')->__('In Stock');
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
            ->columns(array('product_id', 'is_in_stock'));

        $this->_entityAttributeValues = $adapter->fetchPairs($select);

        return $this;
    }
}