<?php

class MiraklSeller_Api_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    /**
     * @return  string
     */
    public function getDeveloperConfigUrl()
    {
        return $this->getUrl('*/system_config/edit', array('section' => 'mirakl_seller_api_developer'));
    }

    /**
     * @return  bool
     */
    public function isApiLogEnabled()
    {
        return Mage::helper('mirakl_seller_api/config')->isApiLogEnabled();
    }
}