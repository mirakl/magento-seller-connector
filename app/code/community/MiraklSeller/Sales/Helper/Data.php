<?php

use Mirakl\MMP\Shop\Domain\Order\ShopOrder as MiraklOrder;

class MiraklSeller_Sales_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var array
     */
    protected $_orderStatusList = array(
        'STAGING'                => 'Fraud Check Pending',
        'VALIDATED'              => 'Fraud Check Validation',
        'WAITING_ACCEPTANCE'     => 'Pending Acceptance',
        'REFUSED'                => 'Rejected',
        'WAITING_DEBIT'          => 'Pending Debit',
        'WAITING_DEBIT_PAYMENT'  => 'Debit in Progress',
        'PAID'                   => 'Debited',
        'SHIPPING'               => 'Shipping in Progress',
        'TO_COLLECT'             => 'To Collect',
        'SHIPPED'                => 'Shipped',
        'RECEIVED'               => 'Received',
        'INCIDENT_OPEN'          => 'Incident Open',
        'INCIDENT_CLOSED'        => 'Incident Closed',
        'CLOSED'                 => 'Closed',
        'CANCELED'               => 'Canceled',
        'WAITING_REFUND'         => 'Pending Refund',
        'WAITING_REFUND_PAYMENT' => 'Refund in Progress',
        'REFUNDED'               => 'Refunded',
    );

    /**
     * @var array
     */
    protected $_paymentWorkflowList = array(
        'PAY_ON_ACCEPTANCE' => 'Pay on acceptance',
        'PAY_ON_DELIVERY'   => 'Pay on delivery',
        'PAY_ON_DUE_DATE'   => 'Pay on due date',
    );

    /**
     * Returns list of available Mirakl order statuses
     *
     * @param   bool    $translated
     * @return  array
     */
    public function getOrderStatusList($translated = true)
    {
        $orderStatuses = $this->_orderStatusList;

        if ($translated) {
            array_walk($orderStatuses, function (&$value) {
                $value = $this->__($value);
            });
        }

        return $orderStatuses;
    }

    /**
     * Returns the status label of the given Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @param   bool        $translated
     * @return  string
     */
    public function getOrderStatusLabel(MiraklOrder $miraklOrder, $translated = true)
    {
        $statusList = $this->getOrderStatusList($translated);
        $status     = $miraklOrder->getStatus()->getState();

        return isset($statusList[$status]) ? $statusList[$status] : $status;
    }

    /**
     * Returns list of available Mirakl payment workflows
     *
     * @param   bool    $translated
     * @return  array
     */
    public function getPaymentWorkflowList($translated = true)
    {
        $paymentWorkflows = $this->_paymentWorkflowList;

        if ($translated) {
            array_walk($paymentWorkflows, function (&$value) {
                $value = $this->__($value);
            });
        }

        return $paymentWorkflows;
    }

    /**
     * Returns the payment workflow label of the given Mirakl order
     *
     * @param   MiraklOrder $miraklOrder
     * @param   bool        $translated
     * @return  string
     */
    public function getPaymentWorkflowLabel(MiraklOrder $miraklOrder, $translated = true)
    {
        $workflowList = $this->getPaymentWorkflowList($translated);
        $workflow     = $miraklOrder->getPaymentWorkflow();

        return isset($workflowList[$workflow]) ? $workflowList[$workflow] : $workflow;
    }
}
