<?php

use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Sales_Block_Adminhtml_Sales_Order_View_Thread_Js extends Mage_Adminhtml_Block_Template
{
    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * Retrieve order model instance
     *
     * @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * @return  string
     */
    public function getThreadViewUrl()
    {
        return $this->getUrl('*/mirakl_seller_thread/view', array('_current' => true));
    }
}