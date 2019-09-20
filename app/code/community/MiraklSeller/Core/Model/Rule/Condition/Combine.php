<?php

class MiraklSeller_Core_Model_Rule_Condition_Combine extends Mage_CatalogRule_Model_Rule_Condition_Combine
{
    /**
     * @var mixed
     */
    protected $_value;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('mirakl_seller/rule_condition_combine');
    }

    /**
     * {@inheritdoc}
     */
    public function asHtmlRecursive()
    {
        $html = $this->asHtml().'<ul id="'.$this->getPrefix().'__'.$this->getId().'__children" class="rule-param-children">';
        foreach ($this->getConditions() as $cond) {
            /** @var Mage_Rule_Model_Condition_Abstract $cond */
            try {
                $html .= '<li>' . $cond->asHtmlRecursive() . '</li>';
            } catch (Exception $e) {
                $html .= sprintf(
                    '<li>%s&nbsp;<span class="error">%s</span>%s</li>',
                    $cond->getAttributeName(),
                    $e->getMessage(),
                    $cond->getRemoveLinkHtml()
                );
            }
        }

        $html .= '<li>' . $this->getNewChildElement()->getHtml() . '</li></ul>';

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewChildSelectOptions()
    {
        $productCondition = Mage::getModel('mirakl_seller/rule_condition_product');
        $productAttributes = $productCondition->loadAttributeOptions()->getAttributeOption();
        $attributes = array();
        foreach ($productAttributes as $code => $label) {
            $attributes[] = array('value' => 'mirakl_seller/rule_condition_product|' . $code, 'label' => $label);
        }

        $conditions = Mage_Rule_Model_Condition_Combine::getNewChildSelectOptions();

        $conditions = array_merge_recursive(
            $conditions, array(
                array(
                    'label' => Mage::helper('catalogrule')->__('Conditions Combination'),
                    'value' => 'mirakl_seller/rule_condition_combine',
                ),
                array(
                    'label' => Mage::helper('mirakl_seller')->__('Product Special Condition'),
                    'value' => array(
                        array(
                            'value' => 'mirakl_seller/rule_condition_product_salable|is_salable',
                            'label' => Mage::helper('mirakl_seller')->__('Is Salable'),
                        ),
                        array(
                            'value' => 'mirakl_seller/rule_condition_product_promo|in_promo',
                            'label' => Mage::helper('mirakl_seller')->__('In Promo'),
                        ),
                        array(
                            'value' => 'mirakl_seller/rule_condition_product_quantity|quantity',
                            'label' => Mage::helper('mirakl_seller')->__('Quantity In Stock'),
                        ),
                        array(
                            'value' => 'mirakl_seller/rule_condition_product_stock|stock',
                            'label' => Mage::helper('mirakl_seller')->__('In Stock'),
                        ),
                        array(
                            'value' => 'mirakl_seller/rule_condition_product_price_special_applied|price_special_applied',
                            'label' => Mage::helper('mirakl_seller')->__('Special Price Applied'),
                        ),
                    ),
                ),
                array(
                    'label' => Mage::helper('catalogrule')->__('Product Attribute'),
                    'value' => $attributes,
                ),
            )
        );

        return $conditions;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(Varien_Object $object)
    {
        $conds = $this->getConditions();
        if (!$conds) {
            return true;
        }

        $all  = $this->getAggregator() === 'all';
        $true = (bool) $this->getValue();

        foreach ($conds as $cond) {
            $validated = $cond->validate($object);

            if ($all && $validated !== $true) {
                return false;
            } elseif (!$all && $validated === $true) {
                return true;
            }
        }

        return $all ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        if (null === $this->_value) {
            $this->_value = parent::getValue();
        }

        return $this->_value;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return $this->_getData('prefix');
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions()
    {
        $prefix = $this->getPrefix();

        return $this->_getData($prefix ? $prefix : 'conditions');
    }

    /**
     * {@inheritdoc}
     */
    public function getAggregator()
    {
        return $this->_getData('aggregator');
    }
}
