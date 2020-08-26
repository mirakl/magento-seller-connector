<?php

use Mirakl\MMP\Common\Domain\Order\OrderState;

class MiraklSeller_Sales_Model_Observer_Shipment extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept order shipping from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onSaveShipmentBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $action->getRequest();

        $shipmentQtys = $request->getParam('shipment');
        if (empty($shipmentQtys['items']) || !($qtyToShip = array_sum($shipmentQtys['items']))) {
            return;
        }

        $connection  = $this->_getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());

        try {
            // Synchronize Magento and Mirakl orders together
            $this->_synchronizeOrder->synchronize($order, $miraklOrder);

            if ($qtyToShip < $this->_getOrderQtyToShip($order)) {
                // Block partial shipping
                $this->_fail($this->__('Partial shipping is not allowed on this Mirakl order.'), $action);
            }
        } catch (\Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred: %s', $e->getMessage()));
        }
    }

    /**
     * Intercept order shipping manual or automatic save
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onShipmentSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        $order = $shipment->getOrder();
        if (!$this->_isImportedMiraklOrder($order)) {
            return; // Not a Mirakl order, leave
        }

        if ($this->_getOrderQtyToShip($order) > 0) {
            return; // Order is not totally shipped, abort
        }

        $connection  = $this->_getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());

        try {
            /** @var Mage_Sales_Model_Order_Shipment_Track $track */
            foreach ($shipment->getAllTracks() as $track) {
                // Send order tracking info to Mirakl
                $this->_apiOrder->updateOrderTrackingInfo(
                    $connection,
                    $miraklOrder->getId(),
                    '', // Carrier code may not be present in Mirakl and is not mandatory
                    $track->getTitle(),
                    $track->getNumber()
                );
                break; // Stop after the first, Mirakl handles only one tracking
            }

            // Confirm shipment of the order in Mirakl
            if ($miraklOrder->getStatus()->getState() == OrderState::SHIPPING) {
                $this->_apiOrder->shipOrder($connection, $miraklOrder->getId());
            }
        } catch (\Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred: %s', $e->getMessage()));
        }
    }

    /**
     * Returns order total quantity to ship
     *
     * @param   Mage_Sales_Model_Order  $order
     * @return  int
     */
    protected function _getOrderQtyToShip($order)
    {
        $qtyToShip = 0;
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $qtyToShip += $item->getQtyToShip();
        }

        return $qtyToShip;
    }
}