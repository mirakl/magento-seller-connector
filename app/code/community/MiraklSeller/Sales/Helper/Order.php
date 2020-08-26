<?php

use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use Mirakl\MMP\Common\Domain\Order\State\OrderStatus;
use Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount;
use Mirakl\MMP\Shop\Domain\Collection\Order\ShopOrderCollection;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use MiraklSeller_Api_Model_Connection as Connection;
use MiraklSeller_Sales_Exception_AlreadyExistsException as AlreadyExistsException;

class MiraklSeller_Sales_Helper_Order extends Mage_Core_Helper_Abstract
{
    /**
     * @param   string  $status
     * @return  bool
     */
    public function canImport($status)
    {
        return in_array(
            $status, array(
                OrderStatus::SHIPPING,
                OrderStatus::SHIPPED,
                OrderStatus::TO_COLLECT,
                OrderStatus::RECEIVED,
                OrderStatus::CLOSED,
            )
        );
    }

    /**
     * Converts a Mirakl order into a Magento order
     *
     * @param   ShopOrder   $miraklOrder
     * @param   mixed       $store
     * @return  Mage_Sales_Model_Order
     */
    public function createOrder(ShopOrder $miraklOrder, $store = null)
    {
        $order = Mage::getModel('mirakl_seller_sales/create_order')->create($miraklOrder, $store);

        $config = Mage::helper('mirakl_seller_sales/config');

        if ($config->isAutoCreateInvoice() && $miraklOrder->getPaymentWorkflow() == 'PAY_ON_ACCEPTANCE') {
            Mage::getModel('mirakl_seller_sales/create_invoice')->create($order);
        }

        if ($config->isAutoCreateShipment() && $this->isMiraklOrderShipped($miraklOrder)) {
            Mage::getModel('mirakl_seller_sales/create_shipment')->create($order, $miraklOrder);
        }

        return $order;
    }

    /**
     * @param   string|null $locale
     * @return  array
     */
    public function getCountryList($locale = null)
    {
        if (null === $locale) {
            $locale = Mage::app()->getLocale()->getLocale();
        }

        return \Zend_Locale::getTranslationList('territory', $locale, 2);
    }

    /**
     * @param   Connection  $connection
     * @return  Mage_Sales_Model_Resource_Order_Collection
     */
    public function getMagentoOrdersByConnection(Connection $connection)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToFilter('mirakl_connection_id', $connection->getId());

        return $collection;
    }

    /**
     * Retrieves the Magento orders associated with the specified Mirakl order ids
     *
     * @param   array   $miraklOrderIds
     * @return  Mage_Sales_Model_Resource_Order_Collection
     */
    public function getMagentoOrdersByMiraklOrderIds(array $miraklOrderIds)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection();

        if (empty($miraklOrderIds)) {
            $collection->addFieldToFilter('entity_id', 0); // Must return an empty collection
        } else {
            $collection->addFieldToFilter('mirakl_order_id', $miraklOrderIds);
        }

        return $collection;
    }

    /**
     * Returns a Magento order associated with the specified Mirakl order id if exists
     *
     * @param   string  $miraklOrderId
     * @return  Mage_Sales_Model_Order|null
     */
    public function getOrderByMiraklOrderId($miraklOrderId)
    {
        /** @var Mage_Sales_Model_Resource_Order_Collection $collection */
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToFilter('mirakl_order_id', $miraklOrderId);

        return $collection->count() ? $collection->getFirstItem() : null;
    }

    /**
     * @param   ShopOrderLine   $miraklOrderLine
     * @return  float
     */
    public function getMiraklOrderLineShippingTaxAmount(ShopOrderLine $miraklOrderLine)
    {
        $taxAmount = 0;

        if ($miraklOrderLine->getStatus()->getState() !== OrderState::REFUSED) {
            /** @var OrderTaxAmount $shippingTax */
            foreach ($miraklOrderLine->getShippingTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @return  float
     */
    public function getMiraklOrderShippingTaxAmount(ShopOrder $miraklOrder)
    {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineShippingTaxAmount($orderLine);
        }

        return $taxAmount;
    }

    /**
     * @param   ShopOrderLine   $miraklOrderLine
     * @param   bool            $withShipping
     * @return  float
     */
    public function getMiraklOrderLineTaxAmount(ShopOrderLine $miraklOrderLine, $withShipping = false)
    {
        $taxAmount = 0;

        if ($miraklOrderLine->getStatus()->getState() !== OrderState::REFUSED) {
            /** @var OrderTaxAmount $shippingTax */
            foreach ($miraklOrderLine->getTaxes() as $tax) {
                $taxAmount += $tax->getAmount();
            }
        }

        return $taxAmount + ($withShipping ? $this->getMiraklOrderLineShippingTaxAmount($miraklOrderLine) : 0);
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @param   bool        $withShipping
     * @return  float
     */
    public function getMiraklOrderTaxAmount(ShopOrder $miraklOrder, $withShipping = false)
    {
        $taxAmount = 0;

        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            $taxAmount += $this->getMiraklOrderLineTaxAmount($orderLine, $withShipping);
        }

        return $taxAmount;
    }

    /*
     * @param   Connection  $connection
     * @param   ShopOrder   $miraklOrder
     * @return  Mage_Sales_Model_Order|false
     * @throws  \Exception
     */
    public function importMiraklOrder(Connection $connection, ShopOrder $miraklOrder)
    {
        if (!$this->canImport($miraklOrder->getStatus()->getState())) {
            throw new \Exception($this->__('The Mirakl order #%s cannot be imported', $miraklOrder->getId()));
        }

        if ($order = $this->getOrderByMiraklOrderId($miraklOrder->getId())) {
            throw new AlreadyExistsException(
                $this->__(
                    'The Mirakl order #%s has already been imported (#%s)', $miraklOrder->getId(), $order->getIncrementId()
                )
            );
        }

        // Create the Magento order
        $order = $this->createOrder($miraklOrder, $connection->getStoreId());

        // Save some Mirakl information to be able to associate actions on it later
        $order->setMiraklConnectionId($connection->getId());
        $order->setMiraklOrderId($miraklOrder->getId());
        $order->save();

        return $order;
    }

    /**
     * Imports specified Mirakl orders into Magento and returns the ids of the imported orders
     *
     * @param   Connection          $connection
     * @param   ShopOrderCollection $miraklOrders
     * @return  array
     */
    public function importMiraklOrders(Connection $connection, ShopOrderCollection $miraklOrders)
    {
        $importedMiraklOrderIds = array();

        /** @var \Mirakl\MMP\Shop\Domain\Order\ShopOrder $miraklOrder */
        foreach ($miraklOrders as $miraklOrder) {
            try {
                $this->importMiraklOrder($connection, $miraklOrder);
                $importedMiraklOrderIds[] = $miraklOrder->getId();
            } catch (AlreadyExistsException $e) {
                // Ignore already existing imported orders
            }
        }

        return $importedMiraklOrderIds;
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isMiraklOrderInvoiced($miraklOrder)
    {
        return !empty($miraklOrder->getCustomerDebitedDate());
    }

    /**
     * Returns true if given Mirakl order has been shipped
     *
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function isMiraklOrderShipped(ShopOrder $miraklOrder)
    {
        return in_array(
            $miraklOrder->getStatus()->getState(), array(
                OrderStatus::SHIPPED,
                OrderStatus::TO_COLLECT,
                OrderStatus::RECEIVED,
                OrderStatus::CLOSED,
            )
        );
    }
}
