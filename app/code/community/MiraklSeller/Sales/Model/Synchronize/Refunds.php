<?php

use Mage_Sales_Model_Order as Order;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Model_Synchronize_Refunds
{
    /**
     * @var MiraklSeller_Sales_Model_Create_Refund
     */
    protected $_createRefund;

    /**
     * @var MiraklSeller_Sales_Model_Synchronize_Creditmemo
     */
    protected $_synchronizeCreditMemo;

    /**
     * @var MiraklSeller_Sales_Helper_Creditmemo
     */
    protected $_creditMemoHelper;

    public function __construct()
    {
        $this->_createRefund = Mage::getModel('mirakl_seller_sales/create_refund');
        $this->_synchronizeCreditMemo = Mage::getModel('mirakl_seller_sales/synchronize_creditmemo');
        $this->_creditMemoHelper = Mage::helper('mirakl_seller_sales/creditmemo');
    }

    /**
     * Returns true if Magento order has been updated or false if nothing has changed (order is up to date with Mirakl)
     *
     * @param   Order       $magentoOrder
     * @param   ShopOrder   $miraklOrder
     * @return  bool
     */
    public function synchronize(Order $magentoOrder, ShopOrder $miraklOrder)
    {
        $updated = false; // Flag to mark Magento order as updated or not

        /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
        foreach ($miraklOrder->getOrderLines() as $orderLine) {
            /** @var \Mirakl\MMP\Common\Domain\Order\Refund $refund */
            foreach ($orderLine->getRefunds() as $refund) {
                $existingCreditMemo = $this->_creditMemoHelper->getCreditMemoByMiraklRefundId($refund->getId());
                if ($existingCreditMemo->getId()) {
                    if ($this->_synchronizeCreditMemo->synchronize($existingCreditMemo, $refund)) {
                        $updated = true;
                    }
                } elseif ($magentoOrder->canCreditmemo() && null !== $this->_createRefund->create($magentoOrder, $orderLine, $refund)) {
                    $updated = true;
                }
            }
        }

        return $updated;
    }
}