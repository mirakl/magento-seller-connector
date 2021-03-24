<?php

class MiraklSeller_Sales_Helper_Shipment extends Mage_Core_Helper_Abstract
{
    /**
     * @param   string  $miraklShipmentId
     * @return  Mage_Sales_Model_Order_Shipment
     */
    public function getShipmentByMiraklShipmentId($miraklShipmentId)
    {
        /** @var Mage_Sales_Model_Order_Shipment $shipment */
        $shipment = Mage::getResourceModel('sales/order_shipment_collection')
            ->addFieldToFilter('mirakl_shipment_id', $miraklShipmentId)
            ->getFirstItem();

        return $shipment;
    }
}
