<?php

use Mage_Core_Controller_Varien_Action as Action;
use Mage_Sales_Model_Order_Shipment_Track as Track;
use MiraklSeller_Api_Model_Connection as Connection;
use Mirakl\MMP\Common\Domain\Shipment\Shipment as MiraklShipment;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

abstract class MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * @var MiraklSeller_Api_Helper_Order
     */
    protected $_apiOrder;

    /**
     * @var MiraklSeller_Api_Helper_Shipment
     */
    protected $_apiShipment;

    /**
     * @var MiraklSeller_Sales_Model_Synchronize_Order
     */
    protected $_synchronizeOrder;

    /**
     * @var MiraklSeller_Core_Helper_Connection
     */
    protected $_connectionHelper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_apiOrder         = Mage::helper('mirakl_seller_api/order');
        $this->_apiShipment      = Mage::helper('mirakl_seller_api/shipment');
        $this->_synchronizeOrder = Mage::getModel('mirakl_seller_sales/synchronize_order');
        $this->_connectionHelper = Mage::helper('mirakl_seller/connection');
    }

    /**
     * @return  string
     */
    protected function __()
    {
        return call_user_func_array(array(Mage::helper('mirakl_seller_sales'), '__'), func_get_args());
    }

    /**
     * Retrieves Mirakl connection by id
     *
     * @param   int $connectionId
     * @return  Connection
     */
    protected function _getConnectionById($connectionId)
    {
        return Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
    }

    /**
     * @param   Connection  $connection
     * @param   string      $miraklOrderId
     * @param   string      $miraklShipmentId
     * @return  MiraklShipment
     * @throws  \Exception
     */
    protected function _getMiraklShipment(Connection $connection, $miraklOrderId, $miraklShipmentId)
    {
        $shipments = $this->_apiShipment->getShipments($connection, array($miraklOrderId));

        /** @var MiraklShipment $shipment */
        foreach ($shipments->getCollection() as $shipment) {
            if ($shipment->getId() === $miraklShipmentId) {
                return $shipment;
            }
        }

        $this->_fail($this->__(
            "Could not find Mirakl order shipment for id '%s' with order '%s' and connection '%s'.",
            $miraklShipmentId, $miraklOrderId, $connection->getId()
        ));
    }

    /**
     * Redirects user to HTTP_REFERER with an error if possible or throw an exception
     *
     * @param   string      $msg
     * @param   Action|null $action
     * @throws  Mage_Core_Exception
     */
    protected function _fail($msg, Action $action = null)
    {
        if ($action && ($refererUrl = $action->getRequest()->getServer('HTTP_REFERER'))) {
            Mage::getSingleton('adminhtml/session')->addError($msg);
            $action->setFlag('', Action::FLAG_NO_DISPATCH, true);
            $action->getResponse()->setRedirect($refererUrl);
            $action->getResponse()->sendHeadersAndExit();
        }

        Mage::throwException($msg);
    }

    /**
     * Returns Magento order ONLY IF linked to a Mirakl order
     *
     * @param   Varien_Event    $event
     * @return  Mage_Sales_Model_Order|null
     */
    protected function _getOrderFromEvent(Varien_Event $event)
    {
        /** @var Mage_Adminhtml_Controller_Action $action */
        $action  = $event->getControllerAction();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $action->getRequest();

        /** @var Mage_Sales_Model_Order $order */
        $orderId = $request->getParam('order_id');
        $order   = Mage::getModel('sales/order')->load($orderId);

        return $this->_isImportedMiraklOrder($order) ? $order : null;
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

    /**
     * @param   Connection  $connection
     * @param   Track       $track
     * @return  string
     */
    public function getMiraklCarrierCode(Connection $connection, Track $track)
    {
        $mapping = $connection->getCarriersMapping();

        if (isset($mapping[$track->getCarrierCode()])) {
            return $mapping[$track->getCarrierCode()];
        }

        return '';
    }

    /**
     * @param   Connection  $connection
     * @param   string      $miraklOrderId
     * @return  ShopOrder
     * @throws  Mage_Core_Exception
     */
    protected function _getMiraklOrder(Connection $connection, $miraklOrderId)
    {
        $miraklOrder = $this->_apiOrder->getOrderById($connection, $miraklOrderId);

        if (!$miraklOrder) {
            $this->_fail(
                $this->__(
                    "Could not find Mirakl order for id '%s' with connection '%s'.", $miraklOrderId, $connection->getId()
                )
            );
        }

        Mage::register('mirakl_order', $miraklOrder, true);

        return $miraklOrder;
    }

    /**
     * @return  Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * @param   Mage_Sales_Model_Order  $order
     * @return  bool
     */
    protected function _isImportedMiraklOrder(Mage_Sales_Model_Order $order)
    {
        return $order->getId() && $order->getMiraklConnectionId() && $order->getMiraklOrderId();
    }
}