<?php

class MiraklSeller_Sales_Model_Observer_Email extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept send email on order from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onSendEmailBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        $this->_fail(
            $this->__(
                'Sending emails is not possible on a Mirakl order. ' .
                'You can exchange messages with the buyer by using the Comments History section on this order.'
            ), $action
        );
    }
}