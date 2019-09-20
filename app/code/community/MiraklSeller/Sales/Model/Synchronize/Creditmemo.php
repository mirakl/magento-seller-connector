<?php

use Mage_Sales_Model_Order_Creditmemo as CreditMemo;
use Mirakl\MMP\Common\Domain\Order\Refund;
use Mirakl\MMP\Common\Domain\Order\Refund\RefundState;

class MiraklSeller_Sales_Model_Synchronize_Creditmemo
{
    /**
     * Returns true if Magento credit memo has been updated or false if not
     *
     * @param   CreditMemo  $creditMemo
     * @param   Refund      $miraklRefund
     * @return  bool
     */
    public function synchronize(CreditMemo $creditMemo, Refund $miraklRefund)
    {
        $updated = false; // Flag to mark Magento credit memo as updated or not

        if ($creditMemo->getState() == CreditMemo::STATE_OPEN && $miraklRefund->getState() == RefundState::REFUNDED) {
            $creditMemo->setState(CreditMemo::STATE_REFUNDED);
            $creditMemo->save();

            // Save refunded amount
            $magentoOrder = $creditMemo->getOrder();
            $magentoOrder->setBaseTotalRefunded($magentoOrder->getBaseTotalRefunded() + $creditMemo->getBaseGrandTotal());
            $magentoOrder->setTotalRefunded($magentoOrder->getTotalRefunded() + $creditMemo->getGrandTotal());
            $magentoOrder->save();

            $updated = true;
        }

        return $updated;
    }
}