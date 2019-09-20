<?php

class MiraklSeller_Core_Block_Adminhtml_System_Config_Fieldset_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * @var string
     */
    protected $_template = 'mirakl_seller/system/config/fieldset/hint.phtml';

    /**
     * @return  string
     */
    public function getCurrentVersion()
    {
        return Mage::helper('mirakl_seller')->getVersion();
    }

    /**
     * @return  string
     */
    public function getCurrentVersionSDK()
    {
        return Mage::helper('mirakl_seller')->getVersionSDK();
    }

    /**
     * @param   Varien_Data_Form_Element_Abstract   $element
     * @return  string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->toHtml();
    }
}
