<?php

use Mage_Sales_Model_Order as Order;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Model_Synchronize_Order
{
    /**
     * @var MiraklSeller_Sales_Model_Create_Invoice
     */
    protected $_createInvoice;

    /**
     * @var MiraklSeller_Sales_Model_Create_Shipment
     */
    protected $_createShipment;

    /**
     * @var MiraklSeller_Sales_Helper_Config
     */
    protected $_config;

    /**
     * @var MiraklSeller_Sales_Helper_Order
     */
    protected $_orderHelper;

    /**
     * @var MiraklSeller_Sales_Model_Synchronize_Refunds
     */
    protected $_synchronizeRefunds;

    /**
     * @var MiraklSeller_Sales_Model_Synchronize_Shipments
     */
    protected $_synchronizeShipments;

    public function __construct()
    {
        $this->_createInvoice        = Mage::getModel('mirakl_seller_sales/create_invoice');
        $this->_createShipment       = Mage::getModel('mirakl_seller_sales/create_shipment');
        $this->_synchronizeRefunds   = Mage::getModel('mirakl_seller_sales/synchronize_refunds');
        $this->_synchronizeShipments = Mage::getModel('mirakl_seller_sales/synchronize_shipments');
        $this->_config               = Mage::helper('mirakl_seller_sales/config');
        $this->_orderHelper          = Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * Returns true if Magento order has been updated or false if nothing has changed (order is up to date with Mirakl)
     *
     * @param   Order       $magentoOrder
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function synchronize($magentoOrder, $miraklOrder)
    {
        $updated = false; // Flag to mark Magento order as updated or not

        $magentoState = $magentoOrder->getState();
        $miraklState  = $miraklOrder->getStatus()->getState();
        $hasInvoice   = $magentoOrder->getInvoiceCollection()->count();
        $canInvoice   = !$hasInvoice && $this->_config->isAutoCreateInvoice();

        // Cancel Magento order if Mirakl order is canceled
        if ($miraklState == OrderState::CANCELED && !$magentoOrder->isCanceled()) {
            $updated = true;
            $magentoOrder->cancel()->save();
        }

        // Block Magento order if Mirakl order has been refused
        if ($miraklState == OrderState::REFUSED && $magentoState != Order::STATE_HOLDED) {
            $updated = true;
            $magentoOrder->hold()->save();
        }

        // Create Magento invoice if Mirakl order has been invoiced
        if ($canInvoice && $this->_orderHelper->isMiraklOrderInvoiced($miraklOrder)) {
            $updated = true;
            $this->_createInvoice->create($magentoOrder);
        }

        // Synchronize Mirakl shipments with Magento order
        if ($this->_config->isAutoCreateShipment()) {
            $updated = ($updated | $this->_synchronizeShipments->synchronize($magentoOrder, $miraklOrder));
        }

        // Synchronize Mirakl refunds with Magento order
        if ($this->_config->isAutoCreateRefunds()) {
            $updated = ($updated | $this->_synchronizeRefunds->synchronize($magentoOrder, $miraklOrder));
        }

        return (bool) $updated;
    }
}