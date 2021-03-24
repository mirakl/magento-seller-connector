<?php

class MiraklSeller_Api_Block_Adminhtml_Widget_Form_Renderer_CarriersMappingFieldset
    extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->setTemplate('mirakl_seller/widget/form/renderer/carriers_mapping_fieldset.phtml');
    }
}