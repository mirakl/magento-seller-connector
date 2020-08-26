<?php

class MiraklSeller_Sales_Adminhtml_Mirakl_Seller_OrderController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var MiraklSeller_Api_Helper_Order
     */
    protected $_apiHelper;

    /**
     * @var MiraklSeller_Sales_Helper_Order
     */
    protected $_orderHelper;

    protected function _construct()
    {
        parent::_construct();
        $this->_apiHelper   = Mage::helper('mirakl_seller_api/order');
        $this->_orderHelper = Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    protected function _getConnection()
    {
        $connectionId = $this->getRequest()->getParam('connection_id');
        $connection = Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
        if (!$connection->getId()) {
            $this->_redirectErrorMessage($this->__('Could not find connection with id %s', $connectionId));
        }

        return $connection;
    }

    /**
     * @param   MiraklSeller_Api_Model_Connection   $connection
     * @return  \Mirakl\MMP\Shop\Domain\Order\ShopOrder
     */
    protected function _getMiraklOrder(MiraklSeller_Api_Model_Connection $connection)
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->_redirectErrorMessage($this->__('Mirakl order id could not be found'));
        }

        $order = $this->_apiHelper->getOrderById($connection, $orderId);
        if (!$order) {
            $this->_redirectErrorMessage(
                $this->__('Could not find order with id %s on connection %s', $orderId, $connection->getName())
            );
        }

        return $order;
    }

    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mirakl_seller/orders');
    }

    /**
     * @param   string  $message
     * @return  $this
     */
    protected function _redirectErrorMessage($message)
    {
        $this->_getSession()->addError($message);
        $this->_redirect('*/*/list');
        $this->getResponse()->sendHeadersAndExit();
    }

    /**
     * (OR29) Cancel a Mirakl order in Mirakl
     */
    public function cancelAction()
    {
        try {
            // Retrieve connection
            $connection = $this->_getConnection();

            // Retrieve order
            $order = $this->_getMiraklOrder($connection);

            $this->_apiHelper->cancelOrder($connection, $order->getId());

            Mage::dispatchEvent(
                'mirakl_seller_cancel_order_after', array(
                    'connection' => $connection,
                    'order'      => $order,
                )
            );

            $this->_getSession()->addSuccess($this->__('Order has been canceled successfully.'));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirectErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/list');
    }

    /**
     * Import a Mirakl order into Magento
     */
    public function importAction()
    {
        try {
            // Retrieve connection
            $connection = $this->_getConnection();

            // Retrieve Mirakl order
            $miraklOrder = $this->_getMiraklOrder($connection);

            // Import the Mirakl order into Magento
            $order = $this->_orderHelper->importMiraklOrder($connection, $miraklOrder);

            $this->_getSession()->addSuccess(
                $this->__(
                    'Order has been imported successfully: <a href="%s" title="%s">%s</a>.',
                    $this->getUrl('*/sales_order/view', array('order_id' => $order->getId())),
                    $this->__('View imported order'),
                    $order->getIncrementId()
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirectErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/list');
    }

    /**
     * Forward to list
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * (OR21) Accept an order by calling API OR21
     */
    public function acceptAction()
    {
        try {
            // Retrieve connection
            $connection = $this->_getConnection();

            // Retrieve order
            $order = $this->_getMiraklOrder($connection);

            // Build order lines to accept
            $orderLines = array();
            /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
            foreach ($order->getOrderLines() as $orderLine) {
                $orderLines[] = array(
                    'id'       => $orderLine->getId(),
                    'accepted' => true,
                );
            }

            // Accept all order lines of the order
            $this->_apiHelper->acceptOrder($connection, $order->getId(), $orderLines);

            Mage::dispatchEvent(
                'mirakl_seller_accept_order_after', array(
                    'connection' => $connection,
                    'order'      => $order,
                )
            );

            $this->_getSession()->addSuccess($this->__('Order has been accepted successfully.'));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirectErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/list');
    }

    /**
     * (OR21) Refuse an order by calling API OR21
     */
    public function refuseAction()
    {
        try {
            // Retrieve connection
            $connection = $this->_getConnection();

            // Retrieve order
            $order = $this->_getMiraklOrder($connection);

            // Build order lines to refuse
            $orderLines = array();
            /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
            foreach ($order->getOrderLines() as $orderLine) {
                $orderLines[] = array(
                    'id'       => $orderLine->getId(),
                    'accepted' => false,
                );
            }

            // Refuse all order lines of the order
            $this->_apiHelper->acceptOrder($connection, $order->getId(), $orderLines);

            Mage::dispatchEvent(
                'mirakl_seller_refuse_order_after', array(
                    'connection' => $connection,
                    'order'      => $order,
                )
            );

            $this->_getSession()->addSuccess($this->__('Order has been refused successfully.'));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirectErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/list');
    }

    /**
     * List orders
     */
    public function listAction()
    {
        $this->_title($this->__('Mirakl'))
            ->_title($this->__('Mirakl Orders'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/orders');
        $this->renderLayout();
    }

    /**
     * Mass accept Mirakl order lines
     */
    public function massAcceptAction()
    {
        // Retrieve connection
        $connection = $this->_getConnection();

        // Retrieve order
        $order = $this->_getMiraklOrder($connection);

        $acceptedOrderLineIds = array_filter($this->getRequest()->getParam('order_lines', array()));

        try {
            // Build order lines to accept
            $orderLines = array();

            /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
            foreach ($order->getOrderLines() as $orderLine) {
                $orderLines[] = array(
                    'id'       => $orderLine->getId(),
                    'accepted' => in_array($orderLine->getId(), $acceptedOrderLineIds),
                );
            }

            // Accept selected order lines of the order and refuse the others
            $this->_apiHelper->acceptOrder($connection, $order->getId(), $orderLines);

            Mage::dispatchEvent(
                'mirakl_seller_accept_order_after', array(
                    'connection' => $connection,
                    'order'      => $order,
                )
            );

            $this->_getSession()->addSuccess($this->__('Order has been accepted successfully.'));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect(
            '*/*/view', array(
                'connection_id' => $connection->getId(),
                'order_id'      => $order->getId(),
            )
        );
    }

    /**
     * Mass import selected Mirakl orders into Magento
     */
    public function massImportAction()
    {
        // Retrieve connection
        $connection = $this->_getConnection();

        $miraklOrderIds = array_filter($this->getRequest()->getParam('mirakl_orders', array()));

        try {
            $miraklOrders = $this->_apiHelper->getOrders($connection, array('order_ids' => $miraklOrderIds));

            $importedMiraklOrderIds = $this->_orderHelper->importMiraklOrders($connection, $miraklOrders);

            if (empty($importedMiraklOrderIds)) {
                $this->_getSession()->addError($this->__('No valid Mirakl order found to import.'));
            } else {
                $this->_getSession()->addSuccess(
                    $this->__(
                        'Mirakl orders %s have been imported successfully.',
                        implode(', ', $importedMiraklOrderIds)
                    )
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirectErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/list');
    }

    /**
     * View order
     */
    public function viewAction()
    {
        try {
            // Retrieve connection
            $connection = $this->_getConnection();

            // Retrieve order
            $order = $this->_getMiraklOrder($connection);

            Mage::register('mirakl_seller_connection', $connection);
            Mage::register('mirakl_seller_order', $order);

            $this->loadLayout();
            $this->renderLayout();

            return $this;
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_redirectErrorMessage($e->getMessage());
        }

        return $this->_redirect('*/*/list');
    }
}
