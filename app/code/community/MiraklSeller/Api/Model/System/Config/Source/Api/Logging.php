<?php

class MiraklSeller_Api_Model_System_Config_Source_Api_Logging
{
    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach (MiraklSeller_Api_Model_Log_Options::getOptions() as $code => $label) {
            $options[] = array(
                'value' => $code,
                'label' => Mage::helper('mirakl_seller_api')->__($label),
            );
        }

        return $options;
    }
}