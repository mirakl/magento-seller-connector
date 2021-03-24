<?php

class MiraklSeller_Sales_Model_Observer_Invoice extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept order invoicing from back office to avoid partial invoicing and to cancel the order on Mirakl if needed.
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onSaveInvoiceBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $action->getRequest();

        $invoiceQtys = $request->getParam('invoice');
        if (empty($invoiceQtys['items'])) {
            return;
        }

        if (array_sum($invoiceQtys['items']) < $order->getTotalQtyOrdered()) {
            $this->_fail($this->__('Partial invoicing is not allowed on this Mirakl order.'), $action);
        }

        $connection  = $this->_getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());

        if ($miraklOrder->getPaymentWorkflow() != 'PAY_ON_DELIVERY') {
            return; // Do not do anything for payment workflow different than PAY_ON_DELIVERY
        }

        try {
            // Synchronize Magento and Mirakl orders together
            $this->_synchronizeOrder->synchronize($order, $miraklOrder);
        } catch (\Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred: %s', $e->getMessage()));
        }
    }
}