<?php

use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Collection\Order\ShopOrderCollection;

class MiraklSeller_Sales_Block_Adminhtml_Order_List extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * @var MiraklSeller_Api_Model_Resource_Connection_Collection
     */
    protected $_connections;

    /**
     * @var ShopOrderCollection
     */
    protected $_pendingOrders;

    /**
     * @var ShopOrderCollection
     */
    protected $_incidentOrders;

    /**
     * @var MiraklSeller_Api_Helper_Order
     */
    protected $_apiOrder;

    /**
     * Initialize list
     */
    public function __construct()
    {
        parent::__construct();

        $this->_apiOrder   = Mage::helper('mirakl_seller_api/order');
        $this->_blockGroup = 'mirakl_seller_sales';
        $this->_controller = 'adminhtml_order';
        $this->_headerText = $this->__('Mirakl Orders');
        $this->_removeButton('add');
        $this->setTemplate('mirakl_seller/order/widget/grid/container.phtml');
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getCurrentConnection()
    {
        $connection = $this->getConnections()->getItemById($this->getConnectionId());

        if (!$connection) {
            $connection = $this->getConnections()->getFirstItem();
        }

        return $connection;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getConnectionId()
    {
        $defaultConnectionId = $this->getSession()->getMiraklConnectionId();

        if ($connectionId = $this->getRequest()->getParam('connection_id', $defaultConnectionId)) {
            $this->getSession()->setMiraklConnectionId($connectionId);

            return $connectionId;
        }

        return $this->getConnections()->getFirstItem()->getId();
    }

    /**
     * @return  MiraklSeller_Api_Model_Resource_Connection_Collection
     */
    public function getConnections()
    {
        if (null === $this->_connections) {
            $this->_connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();
        }

        return $this->_connections;
    }

    /**
     * No class on header to remove the blank zone before the title
     *
     * @return  string
     */
    public function getHeaderCssClass()
    {
        return '';
    }

    /**
     * @return  ShopOrderCollection
     */
    public function getOrdersWithIncident()
    {
        if (null === $this->_incidentOrders) {
            $params = array('has_incident' => true);
            $this->_incidentOrders = $this->_apiOrder->getOrders($this->getCurrentConnection(), $params);
        }

        return $this->_incidentOrders;
    }

    /**
     * @return  int
     */
    public function getOrdersWithIncidentCount()
    {
        try {
            return $this->getOrdersWithIncident()->getTotalCount();
        } catch (Exception $e) {
            /** @var Mage_Adminhtml_Block_Messages $messagesBlock */
            $messagesBlock = $this->getLayout()->createBlock('adminhtml/messages');
            $messagesBlock->addError($this->__('An error occurred: %s', $e->getMessage()));
            $this->setChild('messages', $messagesBlock);

            return 0;
        }
    }

    /**
     * @return  ShopOrderCollection
     */
    public function getPendingOrders()
    {
        if (null === $this->_pendingOrders) {
            $params = array('order_states' => array(OrderState::WAITING_ACCEPTANCE));
            $this->_pendingOrders = $this->_apiOrder->getOrders($this->getCurrentConnection(), $params);
        }

        return $this->_pendingOrders;
    }

    /**
     * @return  int
     */
    public function getPendingOrdersCount()
    {
        try {
            return $this->getPendingOrders()->getTotalCount();
        } catch (Exception $e) {
            /** @var Mage_Adminhtml_Block_Messages $messagesBlock */
            $messagesBlock = $this->getLayout()->createBlock('adminhtml/messages');
            $messagesBlock->addError($this->__('An error occurred: %s', $e->getMessage()));
            $this->setChild('messages', $messagesBlock);

            return 0;
        }
    }

    /**
     * @return  Mage_Admin_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('admin/session');
    }

    /**
     * @return  string
     */
    public function getSwitchUrl()
    {
        return $this->getUrl('*/*/list');
    }
}
