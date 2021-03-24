<?php

use Varien_Data_Form_Element_Fieldset as Fieldset;

interface MiraklSeller_Core_Model_Listing_Form_Builder_Interface
{
    /**
     * Adds offer additional fields of listing's connection to the listing form
     *
     * @param   Fieldset    $fieldset
     * @param   array       $data
     * @param   array       $params
     * @return  $this
     */
    public function prepareForm(Fieldset $fieldset, &$data, $params = array());
}