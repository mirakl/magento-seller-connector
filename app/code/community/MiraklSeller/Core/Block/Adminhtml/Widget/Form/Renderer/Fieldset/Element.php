<?php

class MiraklSeller_Core_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Element
    extends Mage_Adminhtml_Block_Widget_Form_Renderer_Fieldset_Element
{
    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->setTemplate('mirakl_seller/widget/form/renderer/fieldset/element.phtml');
    }
}