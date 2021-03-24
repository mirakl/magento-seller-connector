<?php

class MiraklSeller_Core_Block_Adminhtml_Listing_Dialog extends Mage_Adminhtml_Block_Template
{
    /**
     * @return  string
     */
    public function getExportProductUrl()
    {
        return $this->getUrl('*/*/exportProduct', array('id' => $this->getRequest()->getParam('id')));
    }
}
