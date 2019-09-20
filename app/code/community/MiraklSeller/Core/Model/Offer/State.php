<?php

class MiraklSeller_Core_Model_Offer_State
{
    const DEFAULT_STATE = '11'; // 11 = 'New' by default in Mirakl

    /**
     * @var array
     */
    protected $_defaultOptions = array(
        '12' => 'Broken product - Not working',
        '1'  => 'Used - Like New',
        '2'  => 'Used - Very Good Condition',
        '3'  => 'Used - Good Condition',
        '4'  => 'Used - Acceptable Condition',
        '5'  => 'Collectors - Like New',
        '6'  => 'Collectors - Very Good Condition',
        '7'  => 'Collectors - Good Condition',
        '8'  => 'Collectors - Acceptable Condition',
        '10' => 'Refurbished',
        '11' => 'New',
    );

    /**
     * @return  array
     */
    public function getDefaultOptions()
    {
        return $this->_defaultOptions;
    }

    /**
     * @return  array
     */
    public function getOptions()
    {
        $options = new Varien_Object(array('values' => $this->getDefaultOptions()));

        Mage::dispatchEvent('mirakl_seller_offer_state_options', array('options' => $options));

        return $options->getData('values');
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = array();

        foreach ($this->getOptions() as $key => $label) {
            $options[] = array(
                'label' => Mage::helper('mirakl_seller')->__($label),
                'value' => $key,
            );
        }

        return $options;
    }
}