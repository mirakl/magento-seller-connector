<?php

use Mage_Sales_Model_Order as Order;
use Mirakl\MMP\Common\Domain\Order\ShopOrderLine;
use Mirakl\MMP\Common\Domain\Order\Refund;

class MiraklSeller_Sales_Model_Create_Refund
{
    /**
     * @param   Mage_Sales_Model_Order  $magentoOrder
     * @param   ShopOrderLine           $miraklOrderLine
     * @param   Refund                  $refund
     * @return  Mage_Sales_Model_Order_Creditmemo|null
     */
    public function create(Order $magentoOrder, ShopOrderLine $miraklOrderLine, Refund $refund)
    {
        if (!$magentoOrder->canCreditmemo()) {
            Mage::throwException('Cannot create credit memo for the order.');
        }

        $existingCreditMemo = Mage::helper('mirakl_seller_sales/creditmemo')
            ->getCreditMemoByMiraklRefundId($refund->getId());
        if ($existingCreditMemo->getId()) {
            return null;
        }

        $orderItem = $this->_getOrderItemBySku($magentoOrder, $miraklOrderLine->getOffer()->getSku());

        if (!$orderItem) {
            return null;
        }

        $setZeroItemQty = false;
        if (!$refund->getQuantity() && $refund->getAmount()) {
            // Set quantity to 1 temporarily to allow credit memo item creation
            $refund->setQuantity(1);
            $setZeroItemQty = true;
        }

        $creditMemoData = array(
            'qtys' => array($orderItem->getId() => $refund->getQuantity()),
        );

        /** @var Mage_Sales_Model_Service_Order $service */
        $service = Mage::getModel('sales/service_order', $magentoOrder);

        /** @var Mage_Sales_Model_Order_Creditmemo $creditMemo */
        $creditMemo = $service->prepareCreditmemo($creditMemoData);

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $creditMemoItem */
        $creditMemoItem = $creditMemo->getItemsCollection()->getFirstItem();

        $itemTax = 0;
        foreach ($refund->getTaxes() as $tax) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
            $itemTax += $tax->getAmount();
        }

        $creditMemoItem->setTaxAmount($itemTax);

        if ($refund->getQuantity()) {
            $creditMemoItem->setBasePrice($refund->getAmount() / $refund->getQuantity());
            $creditMemoItem->setPrice($refund->getAmount() / $refund->getQuantity());
            $creditMemoItem->setBasePriceInclTax($creditMemoItem->getBasePrice() + ($itemTax / $refund->getQuantity()));
            $creditMemoItem->setPriceInclTax($creditMemoItem->getPrice() + ($itemTax / $refund->getQuantity()));
        } else {
            $creditMemoItem->setBasePrice($refund->getAmount());
            $creditMemoItem->setPrice($refund->getAmount());
            $creditMemoItem->setBasePriceInclTax($creditMemoItem->getBasePrice() + $itemTax);
            $creditMemoItem->setPriceInclTax($creditMemoItem->getPrice() + $itemTax);
        }

        $creditMemoItem->setBaseRowTotal($refund->getAmount());
        $creditMemoItem->setRowTotal($refund->getAmount());
        $creditMemoItem->setBaseRowTotalInclTax($refund->getAmount() + $itemTax);
        $creditMemoItem->setRowTotalInclTax($refund->getAmount() + $itemTax);

        if ($setZeroItemQty) {
            $creditMemoItem->setQty(0);
        }

        $shippingTax = 0;
        foreach ($refund->getShippingTaxes() as $tax) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Tax\OrderTaxAmount $tax */
            $shippingTax += $tax->getAmount();
        }

        // Shipping tax amount
        $creditMemo->setBaseShippingTaxAmount($shippingTax);
        $creditMemo->setShippingTaxAmount($shippingTax);

        // Shipping amount excluding tax
        $creditMemo->setBaseShippingAmount($refund->getShippingAmount());
        $creditMemo->setShippingAmount($refund->getShippingAmount());

        // Shipping amount including tax
        $creditMemo->setBaseShippingInclTax($refund->getShippingAmount() + $shippingTax);
        $creditMemo->setShippingInclTax($refund->getShippingAmount() + $shippingTax);

        // Subtotal amount excluding tax
        $creditMemo->setBaseSubtotal($creditMemoItem->getBaseRowTotal());
        $creditMemo->setSubtotal($creditMemoItem->getRowTotal());

        // Subtotal amount including tax
        $creditMemo->setBaseSubtotalInclTax($creditMemoItem->getBaseRowTotalInclTax());
        $creditMemo->setSubtotalInclTax($creditMemoItem->getRowTotalInclTax());

        // Grand total including tax
        $creditMemo->setBaseGrandTotal($creditMemo->getBaseSubtotalInclTax() + $creditMemo->getBaseShippingInclTax());
        $creditMemo->setGrandTotal($creditMemo->getSubtotalInclTax() + $creditMemo->getShippingInclTax());

        // Total tax amount
        $creditMemo->setBaseTaxAmount($itemTax + $shippingTax);
        $creditMemo->setTaxAmount($itemTax + $shippingTax);

        // Credit memo state
        if ($refund->getState() === Refund\RefundState::REFUNDED) {
            $creditMemo->setState(Mage_Sales_Model_Order_Creditmemo::STATE_REFUNDED);

            // Save refunded amount only if refund had been paid
            $magentoOrder->setBaseTotalRefunded($magentoOrder->getBaseTotalRefunded() + $creditMemo->getBaseGrandTotal());
            $magentoOrder->setTotalRefunded($magentoOrder->getTotalRefunded() + $creditMemo->getGrandTotal());
        } else {
            $creditMemo->setState(Mage_Sales_Model_Order_Creditmemo::STATE_OPEN);
        }

        // Save Mirakl refund id on the credit memo to mark it as imported
        $creditMemo->setMiraklRefundId($refund->getId());
        $creditMemo->setMiraklRefundTaxes(json_encode($refund->getTaxes()->toArray()));
        $creditMemo->setMiraklRefundShippingTaxes(json_encode($refund->getShippingTaxes()->toArray()));

        Mage::getModel('core/resource_transaction')
            ->addObject($creditMemo)
            ->addObject($magentoOrder)
            ->save();

        return $creditMemo;
    }

    /**
     * @param   Order   $magentoOrder
     * @param   string  $sku
     * @return  Mage_Sales_Model_Order_Item|null
     */
    protected function _getOrderItemBySku(Order $magentoOrder, $sku)
    {
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($magentoOrder->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getSku() === $sku) {
                return $orderItem;
            }
        }

        return null;
    }
}