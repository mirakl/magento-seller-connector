<?php

class MiraklSeller_Sales_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_PATH_AUTO_ACCEPT_ORDERS_ENABLED              = 'mirakl_seller_sales/order_acceptance/auto_accept';
    const XML_PATH_AUTO_ACCEPT_INSUFFICIENT_STOCK_BEHAVIOR = 'mirakl_seller_sales/order_acceptance/insufficient_stock';
    const XML_PATH_AUTO_ACCEPT_BACKORDER_BEHAVIOR          = 'mirakl_seller_sales/order_acceptance/backorder';
    const XML_PATH_AUTO_ACCEPT_PRICES_VARIATIONS_BEHAVIOR  = 'mirakl_seller_sales/order_acceptance/prices_variations';

    const XML_PATH_AUTO_CREATE_INVOICE  = 'mirakl_seller_sales/order/auto_create_invoice';
    const XML_PATH_AUTO_CREATE_SHIPMENT = 'mirakl_seller_sales/order/auto_create_shipment';
    const XML_PATH_AUTO_CREATE_REFUNDS  = 'mirakl_seller_sales/order/auto_create_refunds';
    const XML_PATH_AUTO_ORDERS_IMPORT   = 'mirakl_seller_sales/order/auto_orders_import';

    /**
     * Returns behavior selected during order auto acceptance process when a product has backorder enabled
     *
     * @see MiraklSeller_Sales_Model_Order_Acceptance_Backorder
     *
     * @return  int
     */
    public function getBackorderBehavior()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_AUTO_ACCEPT_BACKORDER_BEHAVIOR);
    }

    /**
     * Returns behavior selected during order auto acceptance process when a product has not enough stock
     *
     * @see MiraklSeller_Sales_Model_Order_Acceptance_InsufficientStock
     *
     * @return  int
     */
    public function getInsufficientStockBehavior()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_AUTO_ACCEPT_INSUFFICIENT_STOCK_BEHAVIOR);
    }

    /**
     * Returns percentage of price variation allowed during order auto acceptance process
     * when a product has a falling price difference between Mirakl and Magento.
     *
     * Return null = do not care of price difference
     * Return 0% = do not accept any price difference
     * Return 10% = allow Mirakl price to be 10% lower maximum compare to Magento price
     *
     * @return  int|null
     */
    public function getPricesVariationsPercent()
    {
        $value = Mage::getStoreConfig(self::XML_PATH_AUTO_ACCEPT_PRICES_VARIATIONS_BEHAVIOR);

        return $value === '' ? null : min((int) $value, 100);
    }

    /**
     * Returns true if auto acceptance of Mirakl orders is enabled
     *
     * @return  bool
     */
    public function isAutoAcceptOrdersEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_ACCEPT_ORDERS_ENABLED);
    }

    /**
     * Returns true if invoice has to be created automatically when converting the Mirakl order into a new Magento order
     *
     * @return  bool
     */
    public function isAutoCreateInvoice()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CREATE_INVOICE);
    }

    /**
     * Returns true if refunds has to be created automatically when converting the Mirakl order into a new Magento order
     *
     * @return  bool
     */
    public function isAutoCreateRefunds()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CREATE_REFUNDS);
    }

    /**
     * Returns true if shipment has to be created automatically when converting the Mirakl order into a new Magento order
     *
     * @return  bool
     */
    public function isAutoCreateShipment()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CREATE_SHIPMENT);
    }

    /**
     * Returns true if Mirakl orders have to be automatically imported into Magento via Magento cron tasks
     *
     * @return  bool
     */
    public function isAutoOrdersImport()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_ORDERS_IMPORT);
    }
}
