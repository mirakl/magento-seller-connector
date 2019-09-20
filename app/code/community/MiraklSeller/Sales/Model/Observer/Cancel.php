<?php

class MiraklSeller_Sales_Model_Observer_Cancel extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept order cancelation from back office to cancel the order on Mirakl if possible.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onCancelOrderBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        $connection    = $this->_getConnectionById($order->getMiraklConnectionId());
        $miraklOrderId = $order->getMiraklOrderId();
        $miraklOrder   = $this->_getMiraklOrder($connection, $miraklOrderId);

        if ($miraklOrder->getPaymentWorkflow() != 'PAY_ON_DELIVERY') {
            return; // Do not do anything for payment workflow different than PAY_ON_DELIVERY
        }

        try {
            // Synchronize Magento and Mirakl orders together
            $this->_synchronizeOrder->synchronize($order, $miraklOrder);

            // Block order cancelation if not possible
            if (!$miraklOrder->getData('can_cancel')) {
                $this->_fail($this->__('This order cannot be canceled.'), $observer->getEvent()->getControllerAction());
            }

            // Cancel the Mirakl order just before canceling the Magento order
            $this->_apiOrder->cancelOrder($connection, $miraklOrderId);
        } catch (\Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred: %s', $e->getMessage()));
        }
    }
}