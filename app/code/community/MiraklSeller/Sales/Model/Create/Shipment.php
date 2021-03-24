<?php

use Mage_Sales_Model_Order as MagentoOrder;
use Mage_Sales_Model_Order_Shipment as MagentoShipment;
use Mage_Sales_Model_Order_Shipment_Track as MagentoTracking;
use Mirakl\MMP\Common\Domain\Shipment\Shipment as MiraklShipment;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentLine as MiraklShipmentLine;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentTracking as MiraklTracking;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Model_Create_Shipment
{
    /**
     * @deprecated Use createFull() instead
     *
     * @param   MagentoOrder    $order
     * @param   ShopOrder       $miraklOrder
     * @return  MagentoShipment
     * @throws  \Exception
     */
    public function create(MagentoOrder $order, ShopOrder $miraklOrder)
    {
        return $this->createFull($order, $miraklOrder);
    }

    /**
     * @param   MagentoOrder    $order
     * @param   ShopOrder       $miraklOrder
     * @param   array           $qtys
     * @return  MagentoShipment
     */
    public function createFull(MagentoOrder $order, ShopOrder $miraklOrder, array $qtys = array())
    {
        if (!$order->canShip()) {
            Mage::throwException('Cannot do shipment for the order.');
        }

        $shipment = $order->prepareShipment($qtys);

        $miraklShipping = $miraklOrder->getShipping();
        if ($miraklShipping && ($miraklShipping->getCarrierCode() || $miraklShipping->getCarrier())) {
            // Create shipment tracking
            $trackData = array(
                'carrier_code' => $miraklShipping->getCarrierCode()
                    ? $miraklShipping->getCarrierCode()
                    : MagentoTracking::CUSTOM_CARRIER_CODE,
                'title'        => $miraklShipping->getCarrier(),
                'number'       => $miraklShipping->getTrackingNumber(),
            );

            /** @var MagentoTracking $track */
            $track = Mage::getModel('sales/order_shipment_track');
            $track->addData($trackData);
            $shipment->addTrack($track);
        }

        return $this->_saveShipment($shipment);
    }

    /**
     * @param   MagentoOrder    $order
     * @param   MiraklShipment  $miraklShipment
     * @return  MagentoShipment
     * @throws  \Exception
     */
    public function createPartial(MagentoOrder $order, MiraklShipment $miraklShipment)
    {
        if (!$order->canShip()) {
            Mage::throwException('Cannot do shipment for the order.');
        }

        $itemsToShip = array();
        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            /** @var MiraklShipmentLine $miraklShipmentLine */
            foreach ($miraklShipment->getShipmentLines() as $miraklShipmentLine) {
                if ($item->getSku() == $miraklShipmentLine->getOfferSku()) {
                    $itemsToShip[$item->getId()] = $miraklShipmentLine->getQuantity();
                }
            }
        }

        $shipment = $order->prepareShipment($itemsToShip);

        $shipment->setMiraklShipmentId($miraklShipment->getId());

        /** @var MiraklTracking $miraklTracking */
        $miraklTracking = $miraklShipment->getTracking();
        if ($miraklTracking && ($miraklTracking->getCarrierCode() || $miraklTracking->getCarrierName())) {
            // Create shipment tracking
            $trackData = array(
                'carrier_code' => $miraklTracking->getCarrierCode()
                    ? $miraklTracking->getCarrierCode()
                    : MagentoTracking::CUSTOM_CARRIER_CODE,
                'title'        => $miraklTracking->getCarrierName(),
                'number'       => $miraklTracking->getTrackingNumber(),
            );

            /** @var MagentoTracking $track */
            $track = Mage::getModel('sales/order_shipment_track');
            $track->addData($trackData);
            $shipment->addTrack($track);
        }

        return $this->_saveShipment($shipment);
    }

    /**
     * @param   MagentoShipment $shipment
     * @return  MagentoShipment
     */
    protected function _saveShipment(MagentoShipment $shipment)
    {
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);
        $shipment->setFromMirakl(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder());
        $transactionSave->save();

        return $shipment;
    }
}