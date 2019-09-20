<?php

use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Block_Adminhtml_Sales_Order_View_Mirakl extends Mage_Adminhtml_Block_Template
{
    /**
     * @var string
     */
    protected $_template = 'mirakl_seller/sales/order/view/mirakl.phtml';

    /**
     * @var MiraklSeller_Api_Model_Connection
     */
    protected $_connection;

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getConnection()
    {
        if (null === $this->_connection) {
            $connectionId = $this->getMagentoOrder()->getMiraklConnectionId();
            $this->_connection = Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
        }

        return $this->_connection;
    }

    /**
     * @return  Mage_Sales_Model_Order
     */
    public function getMagentoOrder()
    {
        return Mage::registry('sales_order');
    }

    /**
     * @return  ShopOrder
     */
    public function getMiraklOrder()
    {
        return Mage::registry('mirakl_order');
    }

    /**
     * @return  string
     */
    public function getMiraklOrderCustomerName()
    {
        $customer = $this->getMiraklOrder()->getCustomer();

        return $customer->getFirstname() . ' ' . $customer->getLastname();
    }

    /**
     * @return  string
     */
    public function getMiraklOrderStatus()
    {
        return Mage::helper('mirakl_seller_sales')->getOrderStatusLabel($this->getMiraklOrder());
    }

    /**
     * @return  string
     */
    public function getViewMiraklOrderUrl()
    {
        return Mage::helper('mirakl_seller/connection')->getMiraklOrderUrl(
            $this->getConnection(), $this->getMiraklOrder()
        );
    }

    /**
     * @return  string
     */
    public function getViewMiraklOrderInMagentoUrl()
    {
        return $this->getUrl(
            '*/mirakl_seller_order/view', array(
                'order_id'      => $this->getMiraklOrder()->getId(),
                'connection_id' => $this->getConnection()->getId(),
            )
        );
    }

    /**
     * Returns true if a refund has been issued on the given Mirakl order (even if partial), false otherwise.
     *
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isMiraklOrderRefunded(ShopOrder $miraklOrder)
    {
        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if ($orderLine->getRefunds()->count()) {
                return true;
            }
        }

        return false;
    }
}