<?php

class MiraklSeller_Sales_Model_Create_Invoice
{
    /**
     * @param   Mage_Sales_Model_Order  $order
     * @param   array                   $qtys
     * @return  Mage_Sales_Model_Order_Invoice
     */
    public function create(Mage_Sales_Model_Order $order, array $qtys = array())
    {
        if (!$order->canInvoice()) {
            Mage::throwException('Cannot do invoice for the order.');
        }

        $invoice = $order->prepareInvoice($qtys);
        $invoice->addComment('Invoice automatically created by the Mirakl Seller Connector.');
        $invoice->register();
        $invoice->getOrder()->setIsInProcess(true);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transactionSave->save();

        return $invoice;
    }
}