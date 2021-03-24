<?php

class MiraklSeller_Sales_Model_Observer_Connection
{
    /**
     * @var MiraklSeller_Sales_Helper_Order
     */
    protected $_orderHelper;

    /**
     * Init properties
     */
    public function __construct()
    {
        $this->_orderHelper = Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * @param   Varien_Event_Observer   $observer
     * @throws  Mage_Core_Exception
     */
    public function onDeleteBefore(Varien_Event_Observer $observer)
    {
        $connection = $observer->getEvent()->getConnection();
        $orders = $this->_orderHelper->getMagentoOrdersByConnection($connection);

        if ($orders->count()) {
            // Do not allow connection deletion if any order have been imported from it
            Mage::throwException(
                $this->_orderHelper->__(
                    'This connection cannot be deleted, some Magento orders are linked to it.'
                )
            );
        }
    }
}