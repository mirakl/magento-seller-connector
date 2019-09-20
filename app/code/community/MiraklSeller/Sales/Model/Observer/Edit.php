<?php

class MiraklSeller_Sales_Model_Observer_Edit extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept edit order from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onEditOrderStartBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        $this->_fail($this->__('It is not possible to edit this Mirakl order.'), $action);
    }
}