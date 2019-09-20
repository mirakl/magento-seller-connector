<?php

class MiraklSeller_Core_Model_Rule_Condition_Product extends Mage_CatalogRule_Model_Rule_Condition_Product
{
    /**
     * @var string
     */
    protected $_attribute;

    /**
     * @var Mage_Catalog_Model_Resource_Abstract
     */
    protected $_objectResource;

    /**
     * @var string
     */
    protected $_op;

    /**
     * @var boolean
     */
    protected $_isArrayOperatorType;

    /**
     * Get input type directly for performance optimization
     *
     * @return string
     */
    public function getInputType()
    {
        if (null === $this->_inputType) {
            $this->_inputType = parent::getInputType();
        }

        return $this->_inputType;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->getDataSetDefault('operator', null);
    }

    /**
     * Get correct operator for validation
     *
     * @return string
     */
    public function getOperatorForValidate()
    {
        if (null === $this->_op) {
            $this->_op = parent::getOperatorForValidate();
        }

        return $this->_op;
    }

    /**
     * @return string
     */
    public function getAttribute()
    {
        if (null === $this->_attribute) {
            $this->_attribute = $this->_getData('attribute');
        }

        return $this->_attribute;
    }

    /**
     * @param $object
     * @return Mage_Catalog_Model_Resource_Abstract
     */
    public function getObjectResource($object)
    {
        if (null === $this->_objectResource) {
            $this->_objectResource = $object->getResource();
        }

        return $this->_objectResource;
    }

    /**
     * Check if value should be array
     *
     * Depends on operator input type
     *
     * @return bool
     */
    public function isArrayOperatorType()
    {
        if (null === $this->_isArrayOperatorType) {
            $this->_isArrayOperatorType = parent::isArrayOperatorType();
        }

        return $this->_isArrayOperatorType;
    }

    /**
     * Load attribute options but without the need to be flagged as "Use for Promo Rule Conditions"
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $productAttributes = Mage::getResourceSingleton('catalog/product')
            ->loadAllAttributes()
            ->getAttributesByCode();

        $attributes = array();
        foreach ($productAttributes as $attribute) {
            /* @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
            if (!$attribute->isAllowedForRuleCondition()) {
                continue;
            }

            $attributes[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        $this->_addSpecialAttributes($attributes);

        asort($attributes);
        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * Default operator input by type map getter
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        parent::getDefaultOperatorInputByType();

        // Matches regexp
        $this->_defaultOperatorInputByType['string'][]  = '^$';
        $this->_defaultOperatorInputByType['numeric'][] = '^$';
        $this->_defaultOperatorInputByType['date'][]    = '^$';

        // Does not match regexp
        $this->_defaultOperatorInputByType['string'][]  = '!^$';
        $this->_defaultOperatorInputByType['numeric'][] = '!^$';
        $this->_defaultOperatorInputByType['date'][]    = '!^$';

        return $this->_defaultOperatorInputByType;
    }

    /**
     * Default operator options getter
     * Provides all possible operator options
     *
     * @return array
     */
    public function getDefaultOperatorOptions()
    {
        $options        = parent::getDefaultOperatorOptions();
        $options['^$']  = Mage::helper('mirakl_seller')->__('matches regexp');
        $options['!^$'] = Mage::helper('mirakl_seller')->__('does not match regexp');

        return $options;
    }

    /**
     * Retrieve parsed value
     *
     * @return array|string|int|float
     */
    public function getValueParsed()
    {
        if (!$this->getDataSetDefault('value_parsed', null)) {
            $value = $this->_getData('value');
            if ($this->isArrayOperatorType() && is_string($value)) {
                $value = preg_split('#\s*[,;]\s*#', $value, null, PREG_SPLIT_NO_EMPTY);
            }

            $this->setData('value_parsed', $value);
        }

        return $this->_getData('value_parsed');
    }

    /**
     * Validate product attribute value for condition
     *
     * @param Varien_Object $object
     * @return bool
     */
    public function validate(Varien_Object $object)
    {
        $attrCode = $this->getAttribute();
        if ('category_ids' == $attrCode) {
            return $this->validateAttribute($object->getCategoryIds());
        }

        if ('attribute_set_id' == $attrCode) {
            return $this->validateAttribute($object->getDataSetDefault('attribute_set_id', null));
        }

        $oldAttrValue = $object->getDataSetDefault($attrCode, null);
        $object->setData($attrCode, $this->_getAttributeValue($object));
        $result = $this->_validateProduct($object);
        $this->_restoreOldAttrValue($object, $oldAttrValue);

        return (bool) $result;
    }

    /**
     * (For compatibility with Magento prior to 1.9)
     *
     * Validate product
     *
     * @param Varien_Object $object
     * @return bool
     */
    protected function _validateProduct($object)
    {
        return Mage_Rule_Model_Condition_Abstract::validate($object);
    }

    /**
     * (For compatibility with Magento prior to 1.9)
     *
     * Restore old attribute value
     *
     * @param Varien_Object $object
     * @param mixed $oldAttrValue
     */
    protected function _restoreOldAttrValue($object, $oldAttrValue)
    {
        $attrCode = $this->getAttribute();
        if (is_null($oldAttrValue)) {
            $object->unsetData($attrCode);
        } else {
            $object->setData($attrCode, $oldAttrValue);
        }
    }

    /**
     * Validate product attribute value for condition
     *
     * @param   mixed $validatedValue product attribute value
     * @return  bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateAttribute($validatedValue)
    {
        if (is_object($validatedValue)) {
            return false;
        }

        /**
         * Condition attribute value
         */
        $value = $this->getValueParsed();

        /**
         * Comparison operator
         */
        $op = $this->getOperatorForValidate();

        // if operator requires array and it is not, or on opposite, return false
        if ($this->isArrayOperatorType() xor is_array($value)) {
            return false;
        }

        if ($op === '^$' || $op === '!^$') {
            $result = (bool) preg_match($value, $validatedValue);

            return $op === '!^$' ? !$result : $result;
        }

        $result = false;

        switch ($op) {
            case '==': case '!=':
                if (is_array($value)) {
                    if (is_array($validatedValue)) {
                        $result = array_intersect($value, $validatedValue);
                        $result = !empty($result);
                    } else {
                        return false;
                    }
                } else {
                    if (is_array($validatedValue)) {
                        $result = count($validatedValue) == 1 && array_shift($validatedValue) == $value;
                    } else {
                        $result = $this->_compareValues($validatedValue, $value);
                    }
                }
                break;

            case '<=': case '>':
                if (!is_scalar($validatedValue)) {
                    return false;
                } else {
                    $result = $validatedValue <= $value;
                }
                break;

            case '>=': case '<':
                if (!is_scalar($validatedValue)) {
                    return false;
                } else {
                    $result = $validatedValue >= $value;
                }
                break;

            case '{}': case '!{}':
                if (is_scalar($validatedValue) && is_array($value)) {
                    foreach ($value as $item) {
                        if (stripos($validatedValue, $item) !== false) {
                            $result = true;
                            break;
                        }
                    }
                } elseif (is_array($value)) {
                    if (is_array($validatedValue)) {
                        $result = array_intersect($value, $validatedValue);
                        $result = !empty($result);
                    } else {
                        return false;
                    }
                } else {
                    if (is_array($validatedValue)) {
                        $result = in_array($value, $validatedValue);
                    } else {
                        $result = $this->_compareValues($value, $validatedValue, false);
                    }
                }
                break;

            case '()': case '!()':
                if (is_array($validatedValue)) {
                    $result = count(array_intersect($validatedValue, (array)$value))>0;
                } else {
                    $value = (array)$value;
                    foreach ($value as $item) {
                        if ($this->_compareValues($validatedValue, $item)) {
                            $result = true;
                            break;
                        }
                    }
                }
                break;
        }

        if ('!=' == $op || '>' == $op || '<' == $op || '!{}' == $op || '!()' == $op) {
            $result = !$result;
        }

        return $result;
    }

    /**
     * Get attribute value
     *
     * @param Varien_Object $object
     * @return mixed
     */
    protected function _getAttributeValue($object)
    {
        $attrCode = $this->getAttribute();
        $storeId = $object->getDataSetDefault('store_id', null);
        $defaultStoreId = Mage_Core_Model_App::ADMIN_STORE_ID;
        $entityId = $object->getDataSetDefault('entity_id', null);
        $productValues  = isset($this->_entityAttributeValues[$entityId])
            ? $this->_entityAttributeValues[$entityId] : array();
        $defaultValue = isset($productValues[$defaultStoreId])
            ? $productValues[$defaultStoreId] : $object->getDataSetDefault($attrCode, null);
        $value = isset($productValues[$storeId]) ? $productValues[$storeId] : $defaultValue;

        $value = $this->_prepareDatetimeValue($value, $object);
        $value = $this->_prepareMultiselectValue($value, $object);

        return $value;
    }

    /**
     * Prepare datetime attribute value
     *
     * @param mixed $value
     * @param Varien_Object $object
     * @return mixed
     */
    protected function _prepareDatetimeValue($value, $object)
    {
        $resource = $this->getObjectResource($object);
        $attribute = $resource->getAttribute($this->getAttribute());
        if ($attribute && $attribute->getDataSetDefault('backend_type', null) == 'datetime') {
            $value = strtotime($value);
        }

        return $value;
    }

    /**
     * Prepare multiselect attribute value
     *
     * @param mixed $value
     * @param Varien_Object $object
     * @return mixed
     */
    protected function _prepareMultiselectValue($value, $object)
    {
        $resource = $this->getObjectResource($object);
        $attribute = $resource->getAttribute($this->getAttribute());
        if ($attribute && $attribute->getDataSetDefault('frontend_input', null) == 'multiselect') {
            $value = strlen($value) ? explode(',', $value) : array();
        }

        return $value;
    }
}
