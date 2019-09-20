<?php

class MiraklSeller_Sales_Helper_Creditmemo extends Mage_Core_Helper_Abstract
{
    /**
     * @param   Mage_Sales_Model_Order_Creditmemo   $creditMemo
     * @return  array
     */
    public function getFullTaxInfo($creditMemo)
    {
        if (!$creditMemo->getMiraklRefundId()) {
            return Mage::helper('tax')->getCalculatedTaxes($creditMemo->getOrder());
        }

        $allTaxes = array();

        if ($itemTaxes = $creditMemo->getMiraklRefundTaxes()) {
            $allTaxes = json_decode($itemTaxes, true);
        }

        if ($shippingTaxes = $creditMemo->getMiraklRefundShippingTaxes()) {
            $allTaxes = array_merge($allTaxes, json_decode($shippingTaxes, true));
        }

        $fullTaxInfo = array();

        foreach ($allTaxes as $tax) {
            if ($tax['amount'] <= 0) {
                continue;
            }

            if (!isset($fullTaxInfo[$tax['code']])) {
                $fullTaxInfo[$tax['code']] = array(
                    'title'           => $tax['code'],
                    'percent'         => null,
                    'base_tax_amount' => 0,
                    'tax_amount'      => 0,
                );
            }

            $fullTaxInfo[$tax['code']]['base_tax_amount'] += $tax['amount'];
            $fullTaxInfo[$tax['code']]['tax_amount'] += $tax['amount'];
        }

        return $fullTaxInfo;
    }

    /**
     * @param   int $miraklRefundId
     * @return  Mage_Sales_Model_Order_Creditmemo
     */
    public function getCreditMemoByMiraklRefundId($miraklRefundId)
    {
        /** @var Mage_Sales_Model_Order_Creditmemo $creditMemo */
        $creditMemo = Mage::getModel('sales/order_creditmemo')
            ->getCollection()
            ->addFieldToFilter('mirakl_refund_id', $miraklRefundId)
            ->getFirstItem();

        return $creditMemo;
    }
}