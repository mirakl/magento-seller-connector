<?php

use Mirakl\MMP\Common\Domain\Collection\Shipment\CreateShipmentCollection;
use Mirakl\MMP\Common\Domain\Collection\Shipment\ShipmentLineCollection;
use Mirakl\MMP\Common\Domain\Collection\Shipment\UpdateShipmentTrackingCollection;
use Mirakl\MMP\Common\Domain\Shipment\CreateShipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentLine;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentTracking;
use Mirakl\MMP\Common\Domain\Shipment\UpdateShipmentTracking;

class MiraklSeller_Sales_Model_Observer_Shipment extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept order shipping from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onSaveShipmentBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getFromMirakl()) {
            return; // Abort if creation comes from Mirakl synchronization
        }

        $order = $shipment->getOrder();
        if (!$this->_isImportedMiraklOrder($order)) {
            return; // Not a Mirakl order, leave
        }

        $connection  = $this->_getConnectionById($order->getMiraklConnectionId());
        $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());

        $createShipments = new CreateShipmentCollection();
        $shipmentLines = new ShipmentLineCollection();

        /** @var Mage_Sales_Model_Order_Shipment_Item $item */
        foreach ($shipment->getAllItems() as $item) {
            $shipmentLine = new ShipmentLine();
            $shipmentLine->setOfferSku($item->getSku());
            $shipmentLine->setQuantity($item->getQty());
            $shipmentLines->add($shipmentLine);
        }

        $createShipment = new CreateShipment();
        $createShipment->setOrderId($miraklOrder->getId());
        $createShipment->setShipmentLines($shipmentLines);
        $createShipment->setShipped(true);

        /** @var Mage_Sales_Model_Order_Shipment_Track $track */
        foreach ($shipment->getAllTracks() as $track) {
            $shipmentTracking = new ShipmentTracking();
            $shipmentTracking->setCarrierName($track->getTitle());
            $shipmentTracking->setTrackingNumber($track->getNumber());
            $shipmentTracking->setCarrierCode($this->getMiraklCarrierCode($connection, $track));
            $createShipment->setTracking($shipmentTracking);
            break; // Stop after the first tracking, Mirakl handles only one per shipment
        }

        $createShipments->add($createShipment);

        try {
            // Create the shipment in Mirakl (API ST01)
            $createdShipments = $this->_apiShipment->createShipments($connection, $createShipments);

            if (!empty($createdShipments->getShipmentErrors())) {
                $error = $createdShipments->getShipmentErrors()->first();
                if ($error) {
                    Mage::throwException($this->__('An error occurred: %s', $error->getMessage()));
                }
            }

            // Save the Mirakl created shipment id in Magento shipment
            $shipmentSuccess = $createdShipments->getShipmentSuccess()->first();
            $shipment->setMiraklShipmentId($shipmentSuccess->getId());
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());

                if ($result['status'] === 404 && !$this->_getOrderQtyToShip($order)) {
                    // Multi-shipment is probably disabled in Mirakl
                    // Try to send shipment tracking through API 0R23 (send the last one)

                    /** @var Mage_Sales_Model_Order_Shipment_Track $track */
                    foreach (array_reverse($shipment->getAllTracks()) as $track) {
                        // Send order tracking info to Mirakl
                        $this->_apiOrder->updateOrderTrackingInfo(
                            $connection,
                            $miraklOrder->getId(),
                            $this->getMiraklCarrierCode($connection, $track),
                            $track->getTitle(),
                            $track->getNumber()
                        );
                        break; // Stop after the first, Mirakl handles only one tracking
                    }

                    try {
                        // Confirm shipment of the order in Mirakl through API OR24
                        $this->_apiOrder->shipOrder($connection, $miraklOrder->getId());
                    } catch (\Exception $e) {
                        Mage::throwException($this->__('An error occurred: %s', $e->getMessage()));
                    }
                }
            } catch (\InvalidArgumentException $e) {
                Mage::throwException($this->__('An error occurred: %s', $e->getMessage()));
            }
        }
    }

    /**
     * Intercept order shipping track before it is saved
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onSaveShipmentTrackBefore(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order_Shipment_Track $shipmentTracking */
        $shipmentTracking = $observer->getEvent()->getTrack();

        if ($shipmentTracking->getFromMirakl()) {
            return; // Abort if creation comes from Mirakl synchronization
        }

        $order = $shipmentTracking->getShipment()->getOrder();
        if (!$this->_isImportedMiraklOrder($order)) {
            return; // Not a Mirakl order, leave
        }

        $connection = $this->_getConnectionById($order->getMiraklConnectionId());

        // Retrieve associated Magento shipment
        $shipment = $shipmentTracking->getShipment();

        if (!$shipment->getMiraklShipmentId()) {
            $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());
            $miraklShipping = $miraklOrder->getShipping();

            if (!empty($miraklShipping->getCarrierCode()) || !empty($miraklShipping->getCarrier())) {
                return; // Mirakl shipment already has an associated tracking
            }

            try {
                // Send order tracking info to Mirakl
                $this->_apiOrder->updateOrderTrackingInfo(
                    $connection,
                    $order->getMiraklOrderId(),
                    $this->getMiraklCarrierCode($connection, $shipmentTracking),
                    $shipmentTracking->getTitle(),
                    $shipmentTracking->getNumber()
                );

                return;
            } catch (\Exception $e) {
                Mage::throwException($this->__('An error occurred: %s', $e->getMessage()));
            }
        }

        $miraklShipment = $this->_getMiraklShipment($connection, $order->getMiraklOrderId(), $shipment->getMiraklShipmentId());

        if (!empty($miraklShipment->getTracking()->getData())) {
            return; // Mirakl shipment already has an associated tracking
        }

        $updateShipmentTracking = new UpdateShipmentTracking(array(
            'id' => $miraklShipment->getId(),
            'tracking' => array(
                'carrier_name' => $shipmentTracking->getTitle(),
                'tracking_number' => $shipmentTracking->getNumber(),
            ),
        ));
        $updateShipmentTrackings = new UpdateShipmentTrackingCollection();
        $updateShipmentTrackings->add($updateShipmentTracking);

        try {
            // Create the shipment tracking in Mirakl (API ST23)
            $createdTrackings = $this->_apiShipment->updateShipmentTrackings($connection, $updateShipmentTrackings);

            if (!empty($createdTrackings->getShipmentErrors())) {
                $error = $createdTrackings->getShipmentErrors()->first();
                if ($error) {
                    Mage::throwException($this->__('An error occurred: %s', $error->getMessage()));
                }
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());
                Mage::throwException($this->__('An error occurred: %s', $result['message']));
            } catch (\InvalidArgumentException $e) {
                Mage::throwException($this->__('An error occurred: %s', $e->getMessage()));
            }
        }
    }
}