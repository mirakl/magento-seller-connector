<?php

class MiraklSeller_Sales_Model_Observer_Widget extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * @param   Varien_Event_Observer   $observer
     */
    public function onContainerHtmlBefore(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();

        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Shipment_View) {
            $order = $block->getShipment()->getOrder();
            if ($order && $order->getData('mirakl_order_id')) {
                $block->removeButton('save'); // Hide button 'Send Tracking Information' if this is a Mirakl order
            }
        } elseif ($block instanceof Mage_Adminhtml_Block_Sales_Order_Invoice_View) {
            $order = $block->getInvoice()->getOrder();
            if ($order && $order->getData('mirakl_order_id')) {
                $block->removeButton('send_notification'); // No 'Send Email' button needed
            }
        } elseif ($block instanceof Mage_Adminhtml_Block_Sales_Order_Creditmemo_View) {
            $order = $block->getCreditmemo()->getOrder();
            if ($order && $order->getData('mirakl_order_id')) {
                $block->removeButton('send_notification'); // No 'Send Email' button needed
                $block->removeButton('cancel'); // No 'Cancel' button needed
            }
        }
    }
}