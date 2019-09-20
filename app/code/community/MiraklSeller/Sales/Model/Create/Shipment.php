<?php

use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Model_Create_Shipment
{
    /**
     * @param   Mage_Sales_Model_Order  $order
     * @param   ShopOrder               $miraklOrder
     * @param   array                   $qtys
     * @return  Mage_Sales_Model_Order_Shipment
     */
    public function create(Mage_Sales_Model_Order $order, ShopOrder $miraklOrder, array $qtys = array())
    {
        if (!$order->canShip()) {
            Mage::throwException('Cannot do shipment for the order.');
        }

        $shipment = $order->prepareShipment($qtys);

        if ($miraklShipping = $miraklOrder->getShipping()) {
            // Create shipment tracking
            $trackData = array(
                'carrier_code' => $miraklShipping->getCarrierCode(),
                'title'        => $miraklShipping->getCarrier(),
                'number'       => $miraklShipping->getTrackingNumber(),
            );
            $track = Mage::getModel('sales/order_shipment_track');
            $track->addData($trackData);
            $shipment->addTrack($track);
        }

        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder());
        $transactionSave->save();

        return $shipment;
    }
}