<?php

use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Model_Create_Order
{
    /**
     * @var MiraklSeller_Sales_Model_Mapper_Interface
     */
    protected $_addressMapper;

    /**
     * @var MiraklSeller_Sales_Helper_Data
     */
    protected $_helper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_addressMapper = Mage::getModel('mirakl_seller_sales/mapper_address');
        $this->_helper = Mage::helper('mirakl_seller_sales');
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @param   mixed       $store
     * @return  Mage_Sales_Model_Order
     */
    public function create(ShopOrder $miraklOrder, $store = null)
    {
        $store = Mage::app()->getStore($store);
        if ($store->isAdmin()) {
            $store = Mage::app()->getDefaultStoreView();
        }

        $quoteCurrency = Mage::getModel('directory/currency')->load($miraklOrder->getCurrencyIsoCode());

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getModel('sales/quote')
            ->setStoreId($store->getId())
            ->setForcedCurrency($quoteCurrency)
            ->setFromMiraklOrder(true);

        $quote->save();

        $taxAmount = 0;
        $shippingTaxAmount = 0;
        $quoteTaxes = $quoteItemsTaxes = array();

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            if ($orderLine->getStatus()->getState() == 'REFUSED') {
                continue; // Ignore refused items on Mirakl
            }

            $sku = $orderLine->getOffer()->getSku();
            $productId = Mage::getModel('catalog/product')->getIdBySku($sku);
            if (!$productId) {
                Mage::throwException($this->_helper->__('Product "%s" could not be found in Magento catalog.', $sku));
            }

            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($productId);
            if (!$product->getId()) {
                Mage::throwException($this->_helper->__('Could not load product "%s" in Magento catalog.', $sku));
            }

            // Force the product status to 'enabled' because price is set to 0 if status is 'disabled'
            $product->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);

            $buyInfo = array('qty' => $orderLine->getQuantity());
            $product->setPriceCalculation(false);
            $product->setData('price', $orderLine->getOffer()->getPrice());
            $product->setData('final_price', $orderLine->getOffer()->getPrice());
            $product->unsetData('tax_class_id');

            try {
                $quoteItem = $quote->addProduct($product, new Varien_Object($buyInfo));
                $quoteItem->save();

                /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
                foreach ($orderLine->getTaxes() as $tax) {
                    $taxAmount += $tax->getAmount();
                    @$quoteTaxes[$tax->getCode()] += $tax->getAmount();
                    @$quoteItemsTaxes[$quoteItem->getId()][$tax->getCode()] += $tax->getAmount();
                }

                foreach ($orderLine->getShippingTaxes() as $tax) {
                    $shippingTaxAmount += $tax->getAmount();
                    @$quoteTaxes[$tax->getCode()] += $tax->getAmount();
                }
            } catch (\Exception $e) {
                Mage::throwException(
                    $this->_helper->__(
                        'An error occurred for product "%s" (%s): %s', $product->getName(), $sku, $e->getMessage()
                    )
                );
            }
        }

        if (empty($quote->getAllVisibleItems())) {
            Mage::throwException($this->_helper->__('Could not find any valid products for order creation.'));
        }

        $totalTaxAmount = $taxAmount + $shippingTaxAmount;
        $grandTotal = $miraklOrder->getTotalPrice() + $totalTaxAmount;

        $customer = $miraklOrder->getCustomer();
        $locale = $customer->getLocale();

        $billingAddress = $this->_addressMapper->map($customer->getBillingAddress()->toArray(), $locale);
        $quote->getBillingAddress()
            ->addData($billingAddress)
            ->setShouldIgnoreValidation(true);

        $shippingAddress = $this->_addressMapper->map($customer->getShippingAddress()->toArray(), $locale);
        $quote->getShippingAddress()
            ->addData($shippingAddress)
            ->setCollectShippingRates(true)
            ->setShouldIgnoreValidation(true)
            ->collectTotals();

        $quote->setCheckoutMethod('guest')
            ->setCustomerId(null)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(Mage_Customer_Model_Group::NOT_LOGGED_IN_ID);

        $quote->getPayment()->importData(array('method' => 'mirakl'));
        $quote->setBaseCurrencyCode($miraklOrder->getCurrencyIsoCode())
            ->setQuoteCurrencyCode($miraklOrder->getCurrencyIsoCode())
            ->setBaseSubtotal($miraklOrder->getPrice())
            ->setSubtotal($miraklOrder->getPrice())
            ->setBaseGrandTotal($grandTotal)
            ->setGrandTotal($grandTotal)
            ->save();

        /** @var Mage_Sales_Model_Quote_Address_Rate $addressRate */
        $addressRate = Mage::getModel('sales/quote_address_rate');
        $addressRate->setAddress($quote->getShippingAddress())
            ->setAddressId($quote->getShippingAddress()->getId())
            ->setCode('flatrate_flatrate')
            ->setMethod('flatrate')
            ->setCarrier('flatrate')
            ->setCarrierTitle($miraklOrder->getShipping()->getType()->getLabel())
            ->setMethodTitle($miraklOrder->getShipping()->getType()->getLabel())
            ->save();

        $quote->getShippingAddress()
            ->setShippingMethod('flatrate_flatrate')
            ->setShippingDescription($miraklOrder->getShipping()->getType()->getLabel())
            ->setBaseShippingAmount($miraklOrder->getShipping()->getPrice())
            ->setShippingAmount($miraklOrder->getShipping()->getPrice())
            ->setBaseTaxAmount($taxAmount)
            ->setTaxAmount($taxAmount)
            ->setBaseShippingTaxAmount($shippingTaxAmount)
            ->setShippingTaxAmount($shippingTaxAmount)
            ->setBaseShippingInclTax($miraklOrder->getShipping()->getPrice() + $shippingTaxAmount)
            ->setShippingInclTax($miraklOrder->getShipping()->getPrice() + $shippingTaxAmount)
            ->setBaseSubtotal($miraklOrder->getPrice())
            ->setSubtotal($miraklOrder->getPrice())
            ->setBaseSubtotalTotalInclTax($miraklOrder->getPrice() + $taxAmount)
            ->setSubtotalInclTax($miraklOrder->getPrice() + $taxAmount)
            ->setBaseGrandTotal($grandTotal)
            ->setGrandTotal($grandTotal)
            ->addShippingRate($addressRate)
            ->save();

        // Save taxes amount on each quote item before placing the order
        foreach ($quote->getAllVisibleItems() as $item) {
            /** @var Mage_Sales_Model_Quote_Item $item */
            if (!empty($quoteItemsTaxes[$item->getId()])) {
                $itemTaxAmount = array_sum($quoteItemsTaxes[$item->getId()]);
                $item->setTaxAmount($itemTaxAmount);
                $item->setBaseTaxAmount($itemTaxAmount);
                $item->setBaseRowTotalInclTax($item->getBaseRowTotalInclTax() + $itemTaxAmount);
                $item->setRowTotalInclTax($item->getRowTotalInclTax() + $itemTaxAmount);
                $item->setBasePriceInclTax($item->getBasePrice() + ($itemTaxAmount / $item->getQty()));
                $item->setPriceInclTax($item->getPriceInclTax() + ($itemTaxAmount / $item->getQty()));
                $item->setTaxPercent(round(($itemTaxAmount / $item->getRowTotal()) * 100, 2));
                $item->save();
            }
        }

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();

        $order = $service->getOrder();

        $order->setTaxAmount($totalTaxAmount);
        $order->setShippingTaxAmount($shippingTaxAmount);
        $order->save();

        // Save order taxes by code
        foreach ($quoteTaxes as $code => $amount) {
            $data = array(
                'order_id'         => $order->getId(),
                'code'             => $code,
                'title'            => $code,
                'hidden'           => 0,
                'percent'          => 0,
                'priority'         => 0,
                'position'         => 0,
                'amount'           => $amount,
                'base_amount'      => $amount,
                'process'          => 0,
                'base_real_amount' => $amount,
            );

            /** @var Mage_Sales_Model_Order_Tax $orderTax */
            $orderTax = Mage::getModel('tax/sales_order_tax')->setData($data)->save();

            // Save order item taxes by code
            foreach ($quoteItemsTaxes as $quoteItemId => $taxDetails) {
                if ($orderItem = $order->getItemByQuoteItemId($quoteItemId)) {
                    foreach ($taxDetails as $code => $amount) {
                        if ($code === $orderTax->getCode()) {
                            $data = array(
                                'item_id'     => $orderItem->getId(),
                                'tax_id'      => $orderTax->getId(),
                                'tax_percent' => 0
                            );

                            Mage::getModel('tax/sales_order_tax_item')->setData($data)->save();
                        }
                    }
                }
            }
        }

        return $order;
    }
}