<?php

use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller_Api_Model_Connection as Connection;
use MiraklSeller_Process_Model_Process as Process;
use MiraklSeller_Sales_Model_Order_Acceptance_Backorder as Backorder;
use MiraklSeller_Sales_Model_Order_Acceptance_InsufficientStock as InsufficientStock;
use MiraklSeller_Sales_Model_Order_Acceptance_PricesVariations as PricesVariations;

class MiraklSeller_Sales_Helper_Order_Process extends MiraklSeller_Sales_Helper_Order
{
    /**
     * @var MiraklSeller_Sales_Model_Synchronize_Order
     */
    protected $_synchronizeOrder;

    /**
     * @var Backorder
     */
    protected $_backorderHandler;

    /**
     * @var InsufficientStock
     */
    protected $_insufficientStockHandler;

    /**
     * @var PricesVariations
     */
    protected $_pricesVariationsHandler;

    /**
     * @var MiraklSeller_Sales_Helper_Order_Price
     */
    protected $_priceHelper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_synchronizeOrder         = Mage::getSingleton('mirakl_seller_sales/synchronize_order');
        $this->_backorderHandler         = Mage::getModel('mirakl_seller_sales/order_acceptance_backorder');
        $this->_insufficientStockHandler = Mage::getModel('mirakl_seller_sales/order_acceptance_insufficientStock');
        $this->_pricesVariationsHandler  = Mage::getModel('mirakl_seller_sales/order_acceptance_pricesVariations');
        $this->_priceHelper              = Mage::helper('mirakl_seller_sales/order_price');
    }

    /**
     * @param   Process $process
     * @param   int     $connectionId
     * @return  Process
     */
    public function acceptConnectionOrders(Process $process, $connectionId)
    {
        $connection = $this->_getConnectionById($connectionId);

        if (!$connection->getId()) {
            return $process->fail($this->__("Could not find connection with id '%s'", $connectionId));
        }

        $process->output(
            $this->__(
                "Accepting Mirakl orders of connection '%s' (id: %s) ...", $connection->getName(), $connection->getId()
            )
        );

        $params = array('order_states' => array(\Mirakl\MMP\Common\Domain\Order\OrderState::WAITING_ACCEPTANCE));
        $miraklOrders = Mage::helper('mirakl_seller_api/order')->getAllOrders($connection, $params);

        if (!$miraklOrders->count()) {
            return $process->output($this->__('No Mirakl order to accept for this connection'));
        }

        /** @var ShopOrder $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            try {
                $process->output($this->__('Processing Mirakl order #%s ...', $miraklOrder->getId()));
                $this->acceptMiraklOrder($process, $connection, $miraklOrder);
            } catch (\Exception $e) {
                $process->output($this->__('ERROR: %s', $e->getMessage()));
            }
        }

        return $process;
    }

    /**
     * @param   Process     $process
     * @param   Connection  $connection
     * @param   ShopOrder   $miraklOrder
     * @return  Process
     */
    public function acceptMiraklOrder(Process $process, Connection $connection, ShopOrder $miraklOrder)
    {
        // Build order lines to accept
        $orderLines = array();

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $accepted = true; // Order line is accepted by default

            // Try to load associated product
            $product = Mage::getModel('catalog/product');
            $offerSku = $orderLine->getOffer()->getSku();
            $productId = $product->getResource()->getIdBySku($offerSku);

            if (!$productId) {
                // Case we cannot find associated product
                return $process->output($this->__('Product with SKU "%s" was not found. Please handle order manually.', $offerSku));
            }

            // Product has been found in Magento, load it
            $product->load($productId);

            $magentoPrice = $this->_priceHelper->getMagentoPrice($product, $connection, $orderLine->getQuantity());

            // Handle allowed prices variations on product
            $miraklPrice = $orderLine->getOffer()->getPrice();
            if (!$this->_pricesVariationsHandler->isPriceVariationValid((float) $magentoPrice, (float) $miraklPrice)) {
                return $process->output($this->__('Product with SKU "%s" has an invalid price variation. Please handle order manually.', $offerSku));
            }

            // Try to load associated stock item
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);

            if (!$stockItem->getIsInStock()) {
                // Case we have out of stock flag on product
                if ($this->_insufficientStockHandler->isManageOrderManually()) {
                    return $process->output($this->__('Product with SKU "%s" is out of stock. Please handle order manually.', $offerSku));
                }

                $process->output($this->__('Product with SKU "%s" is out of stock. Product refused.', $offerSku));
                $accepted = false; // Insufficient stock config is "auto reject item"
            } elseif ($stockItem->getQty() < $orderLine->getQuantity()) {
                // Case we have stock item qty under order line qty
                if (!$stockItem->getBackorders()) {
                    // Case we have backorders disabled on stock item and not enough stock
                    if ($this->_insufficientStockHandler->isManageOrderManually()) {
                        return $process->output($this->__('Product with SKU "%s" has not enough stock. Please handle order manually.', $offerSku));
                    }

                    $process->output($this->__('Product with SKU "%s" has not enough stock. Product refused.', $offerSku));
                    $accepted = false; // Insufficient stock config is "auto reject item"
                } else {
                    // Case we have backorders allowed on stock item
                    if ($this->_backorderHandler->isManageOrderManually()) {
                        return $process->output($this->__('Product with SKU "%s" has backorders enabled. Please handle order manually.', $offerSku));
                    } elseif ($this->_backorderHandler->isRejectItemAutomatically()) {
                        $process->output($this->__('Product with SKU "%s" has backorders enabled. Product refused.', $offerSku));
                        $accepted = false;
                    } else {
                        $process->output($this->__('Product with SKU "%s" is accepted.', $offerSku));
                    }
                }
            }

            $orderLines[] = array(
                'id'       => $orderLine->getId(),
                'accepted' => $accepted,
            );
        }

        Mage::helper('mirakl_seller_api/order')->acceptOrder($connection, $miraklOrder->getId(), $orderLines);

        $process->output($this->__('Order has been accepted successfully.'));

        return $process;
    }

    /**
     * Synchronize or import all Mirakl orders from specified
     * Mirakl connection using the last synchronization date field.
     *
     * @param   Process $process
     * @param   int     $connectionId
     * @return  Process
     */
    public function synchronizeConnection(Process $process, $connectionId)
    {
        $connection = $this->_getConnectionById($connectionId);

        if (!$connection->getId()) {
            return $process->fail($this->__("Could not find connection with id '%s'", $connectionId));
        }

        $process->output(
            $this->__(
                "Importing Mirakl orders of connection '%s' (id: %s) ...", $connection->getName(), $connection->getId()
            )
        );

        $params = array();
        if ($lastSyncDate = $connection->getLastOrdersSynchronizationDate()) {
            $updatedSince = new \DateTime($lastSyncDate);
            $params['start_update_date'] = $updatedSince->format(\DateTime::ISO8601);
            $process->output($this->__('=> fetching Mirakl orders modified since %s only', $lastSyncDate));
        }

        $now = Varien_Date::now();

        $miraklOrders = Mage::helper('mirakl_seller_api/order')->getAllOrders($connection, $params);

        if (!$miraklOrders->count()) {
            return $process->output($this->__('No Mirakl order to import for this connection'));
        }

        /** @var ShopOrder $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            try {
                $process->output($this->__('Processing Mirakl order #%s ...', $miraklOrder->getId()));
                $this->synchronizeMiraklOrder($process, $connection, $miraklOrder);
            } catch (\Exception $e) {
                $process->output($this->__('ERROR: %s', $e->getMessage()));
            }
        }

        $connection->setLastOrdersSynchronizationDate($now);
        $connection->save();

        return $process;
    }

    /**
     * Retrieves Mirakl connection by specified id
     *
     * @param   int $connectionId
     * @return  Connection
     */
    protected function _getConnectionById($connectionId)
    {
        return Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
    }

    /**
     * Synchronizes the Mirakl order with Magento if already imported or import it otherwise
     *
     * @param   Process     $process
     * @param   Connection  $connection
     * @param   ShopOrder   $miraklOrder
     * @return  Process
     */
    public function synchronizeMiraklOrder(Process $process, Connection $connection, ShopOrder $miraklOrder)
    {
        if ($order = $this->getOrderByMiraklOrderId($miraklOrder->getId())) {
            // Synchronize Magento order if already imported
            if ($this->_synchronizeOrder->synchronize($order, $miraklOrder)) {
                $process->output($this->__('Mirakl order has been synchronized with Magento'));
            } else {
                $process->output($this->__('Mirakl order is already up to date in Magento'));
            }
        } elseif ($this->canImport($miraklOrder->getStatus()->getState())) {
            // Import Mirakl order if possible
            $this->importMiraklOrder($connection, $miraklOrder);
            $process->output($this->__('Mirakl order has been imported in Magento'));
        } else {
            $process->output($this->__('Nothing to do with this Mirakl order'));
        }

        return $process;
    }
}