<?php

class MiraklSeller_Core_Block_Adminhtml_Widget_Form_Renderer_AdditionalFieldsFieldset
    extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->setTemplate('mirakl_seller/widget/form/renderer/additional_fields_fieldset.phtml');
    }
}