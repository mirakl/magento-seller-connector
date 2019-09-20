<?php

interface MiraklSeller_Core_Model_Listing_Builder_Interface
{
    /**
     * Returns array of product ids
     *
     * @param   MiraklSeller_Core_Model_Listing $listing
     * @return  int[]
     */
    public function build(MiraklSeller_Core_Model_Listing $listing);

    /**
     * Customizes listing's form
     *
     * @param   Varien_Data_Form   $form
     * @param   array              $data
     * @return  $this
     */
    public function prepareForm(Varien_Data_Form $form, &$data = array());
}