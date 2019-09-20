<?php

class MiraklSeller_Sales_Model_Observer_View extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept view order from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onViewOrderBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        $connection  = $this->_getConnectionById($order->getMiraklConnectionId());

        try {
            $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());

            $this->_getSession()->addNotice(
                $this->__(
                    'This is a Mirakl Marketplace order from the connection "%s".', $connection->getName()
                )
            );

            $updated = $this->_synchronizeOrder->synchronize($order, $miraklOrder);
            $miraklOrderUrl = $this->_connectionHelper->getMiraklOrderUrl($connection, $miraklOrder);

            if ($updated) {
                $this->_getSession()->addNotice(
                    $this->__(
                        'Your order <a href="%s" target="_blank">%s</a> has been synchronized with Mirakl.',
                        $miraklOrderUrl,
                        $miraklOrder->getId()
                    )
                );
            } else {
                $this->_getSession()->addNotice(
                    $this->__(
                        'Your order <a href="%s" target="_blank">%s</a> is up to date with Mirakl.',
                        $miraklOrderUrl,
                        $miraklOrder->getId()
                    )
                );
            }
        } catch (\Exception $e) {
            $this->_getSession()->addError(
                $this->__('An error occurred while downloading the Mirakl order information: %s', $e->getMessage())
            );
        }
    }

    /**
     * Add a "mirakl-order-view" class to the HTML <body> tag in order to customize the display
     */
    public function onViewOrderRenderBefore()
    {
        if (Mage::registry('mirakl_order')) {
            // Add a body class to flag the current page as a Mirakl order
            Mage::app()->getLayout()->getBlock('root')->addBodyClass('mirakl-order-view');

            // Remove the gift options block
            if ($block = Mage::app()->getLayout()->getBlock('order_tab_info')) {
                $block->unsetChild('gift_options');
            }
        }
    }
}