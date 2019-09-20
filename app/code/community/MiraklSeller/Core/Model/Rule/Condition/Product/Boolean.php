<?php

abstract class MiraklSeller_Core_Model_Rule_Condition_Product_Boolean
    extends MiraklSeller_Core_Model_Rule_Condition_Product
{
    /**
     * @var string
     */
    protected $_inputType = 'boolean';

    /**
     * {@inheritdoc}
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = parent::getValue();

        return null !== $value ? $value : 1;
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
    public function validate(Varien_Object $object)
    {
        if (isset($this->_entityAttributeValues[$object->_getData('entity_id')])) {
            $object->setData($this->_getData('attribute'), $this->_entityAttributeValues[$object->_getData('entity_id')]);
        }

        return (bool) $this->_validateProduct($object);
    }
}
