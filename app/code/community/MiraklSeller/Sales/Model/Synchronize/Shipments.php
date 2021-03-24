<?php

use Mage_Sales_Model_Order as Order;
use Mirakl\MMP\Common\Domain\Shipment\ShipmentStatus;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Sales_Model_Synchronize_Shipments
{
    /**
     * @var MiraklSeller_Sales_Model_Create_Shipment
     */
    protected $_createShipment;

    /**
     * @var MiraklSeller_Sales_Model_Synchronize_Shipment
     */
    protected $_synchronizeShipment;

    /**
     * @var MiraklSeller_Api_Helper_Shipment
     */
    protected $_shipmentApi;

    /**
     * @var MiraklSeller_Sales_Helper_Shipment
     */
    protected $_shipmentHelper;

    /**
     * @var MiraklSeller_Sales_Helper_Order
     */
    protected $_orderHelper;

    /**
     * @var array
     */
    protected $_stateCodes = array(
        ShipmentStatus::SHIPPED,
        ShipmentStatus::TO_COLLECT,
        ShipmentStatus::RECEIVED,
        ShipmentStatus::CLOSED,
    );

    public function __construct()
    {
        $this->_createShipment = Mage::getModel('mirakl_seller_sales/create_shipment');
        $this->_synchronizeShipment = Mage::getModel('mirakl_seller_sales/synchronize_shipment');
        $this->_shipmentApi = Mage::helper('mirakl_seller_api/shipment');
        $this->_shipmentHelper = Mage::helper('mirakl_seller_sales/shipment');
        $this->_orderHelper = Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * @param   Order   $magentoOrder
     * @return  Connection
     */
    protected function _getConnection(Order $magentoOrder)
    {
        $connectionId = $magentoOrder->getMiraklConnectionId();

        return Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
    }

    /**
     * Returns true if Magento order has been updated or false if nothing has changed (order is up to date with Mirakl)
     *
     * @param   Order       $magentoOrder
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function synchronize(Order $magentoOrder, ShopOrder $miraklOrder)
    {
        if (!$magentoOrder->canShip()) {
            return false;
        }

        $updated = false; // Flag to mark Magento order as updated or not

        $connection = $this->_getConnection($magentoOrder);

        try {
            $miraklShipments = $this->_shipmentApi
                ->getShipments($connection, array($miraklOrder->getId()), $this->_stateCodes);

            /** @var \Mirakl\MMP\Common\Domain\Shipment\Shipment $miraklShipment */
            foreach ($miraklShipments->getCollection() as $miraklShipment) {
                $existingShipment = $this->_shipmentHelper->getShipmentByMiraklShipmentId($miraklShipment->getId());
                if ($existingShipment->getId()) {
                    if ($this->_synchronizeShipment->synchronize($existingShipment, $miraklShipment)) {
                        $updated = true;
                    }
                } elseif (null !== $this->_createShipment->createPartial($magentoOrder, $miraklShipment)) {
                    $updated = true;
                }
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());
                $isMiraklOrderShipped = $this->_orderHelper->isMiraklOrderShipped($miraklOrder);

                if ($result['status'] === 404 && $isMiraklOrderShipped) {
                    // Multi-shipment is probably disabled in Mirakl
                    // Try to create a full shipment

                    try {
                        $updated = true;
                        $this->_createShipment->createFull($magentoOrder, $miraklOrder);
                    } catch (\Exception $e) {
                        Mage::throwException($this->_orderHelper->__('An error occurred: %s', $e->getMessage()));
                    }
                }
            } catch (\InvalidArgumentException $e) {
                Mage::throwException($this->_orderHelper->__('An error occurred: %s', $e->getMessage()));
            }
        }

        return $updated;
    }
}
