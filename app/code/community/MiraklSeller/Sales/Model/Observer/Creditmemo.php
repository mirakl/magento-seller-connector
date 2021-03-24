<?php

class MiraklSeller_Sales_Model_Observer_Creditmemo extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept order refund creation from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onNewCreditMemo(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        $connection = $this->_getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());
        $miraklOrderUrl = $this->_connectionHelper->getMiraklOrderUrl($connection, $miraklOrder);

        $this->_fail($this->__(
            'Refund must be created in <a href="%s" target="_blank">Mirakl back office</a>. ' .
            'It will be synchronized automatically in Magento afterwards.',
            $miraklOrderUrl
        ), $action);
    }

    /**
     * Intercept order refund from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onSaveCreditmemoBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $action->getRequest();

        $creditmemoQtys = $request->getParam('creditmemo');
        if (empty($creditmemoQtys['items'])) {
            return;
        }

        $connection = $this->_getConnectionById($order->getMiraklConnectionId());

        $this->_fail(
            $this->__(
                'Refund is not possible on a Mirakl order from Magento. ' .
                'You can go to your <a href="%s" target="_blank">Mirakl back office</a> to handle it.',
                $connection->getBaseUrl()
            ), $action
        );
    }
}