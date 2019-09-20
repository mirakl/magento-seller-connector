<?php

class MiraklSeller_Core_Model_Rule_Condition_Product_Promo
    extends MiraklSeller_Core_Model_Rule_Condition_Product_Boolean
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('product_in_promo');
        $this->setValueName('In Promo');
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeName()
    {
        return Mage::helper('mirakl_seller')->__('In Promo');
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
    public function getValueSelectOptions()
    {
        return array(
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Yes')),
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('No')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function collectValidatedAttributes($productCollection)
    {
        /** @var $adapter Varien_Db_Adapter_Pdo_Mysql */
        $resource = Mage::getSingleton('core/resource');
        $adapter = $resource->getConnection('read');
        $select = $adapter->select()
            ->from(array('price_index' => $resource->getTableName('catalog_product_index_price')), 'entity_id')
            ->group('entity_id')
            ->having(new Zend_Db_Expr('SUM(final_price < price) > 0'));

        $this->_entityAttributeValues = array_flip($adapter->fetchCol($select));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Varien_Object $object)
    {
        $isPromo = isset($this->_entityAttributeValues[$object->_getData('entity_id')]);

        return $this->getValue() ? $isPromo : !$isPromo;
    }
}