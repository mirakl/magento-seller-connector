<?php

use Mirakl\MMP\Common\Domain\Order\OrderState;

class MiraklSeller_Sales_Block_Adminhtml_Order_View extends Mage_Adminhtml_Block_Widget_Container
{
    /**
     * @var MiraklSeller_Sales_Helper_Order
     */
    protected $_helper;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->_helper = Mage::helper('mirakl_seller_sales/order');

        $this->_addButton(
            'back', array(
                'label'   => Mage::helper('adminhtml')->__('Back'),
                'onclick' => "window.location.href = '" . $this->getUrl('*/*') . "'",
                'class'   => 'back',
            )
        );


        if ($this->getMiraklOrder()->getData('can_cancel')) {
            $confirmationMessage = $this->jsQuoteEscape(
                $this->__('Are you sure you want to cancel this order in Mirakl?')
            );
            $this->_addButton(
                'cancel', array(
                    'label'   => $this->__('Cancel Order'),
                    'onclick' => "confirmSetLocation('{$confirmationMessage}', '{$this->getCancelUrl()}')",
                    'class'   => 'cancel',
                )
            );
        }

        if ($this->_canImport()) {
            $confirmText = $this->__('Are you sure you want to import this order in Magento?');
            $this->addButton(
                'import', array(
                    'label'   => $this->__('Import Order'),
                    'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getImportUrl()}')",
                )
            );
        }

        if ($this->_canRefuse()) {
            $confirmationMessage = $this->jsQuoteEscape(
                $this->__('Are you sure you want to refuse this order in Mirakl?')
            );
            $this->_addButton(
                'refuse', array(
                    'label'   => $this->__('Refuse Order'),
                    'onclick' => "confirmSetLocation('{$confirmationMessage}', '{$this->getRefuseUrl()}')",
                    'class'   => 'delete',
                )
            );
        }
    }

    /**
     * @return  bool
     */
    protected function _canImport()
    {
        return !$this->getMagentoOrder() && $this->_helper->canImport($this->getMiraklOrderState());
    }

    /**
     * @return  bool
     */
    protected function _canRefuse()
    {
        return !$this->getMagentoOrder() && $this->_isOrderWaitingAcceptance();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        // Add a notice to explain that the Mirakl order is waiting for item acceptance
        if ($this->_isOrderWaitingAcceptance()) {
            $this->getMessagesBlock()->addNotice($this->__('This order is waiting for your item acceptation.'));
        }

        // Add a notice to explain that the Mirakl order cannot be imported at the moment
        if ($this->_isOrderWaitingDebitPayment()) {
            $this->getMessagesBlock()->addNotice(
                $this->__(
                    'You have accepted this order but it cannot be imported in Magento yet.<br>' .
                    'Import will be possible after the payment is confirmed in Mirakl: ' .
                    'manually from this page or automatically with the cron job.'
                )
            );
        }

        // Add a notice if the Mirakl order can be imported
        if ($this->_canImport()) {
            $this->getMessagesBlock()->addNotice($this->__('You can import your order in Magento.'));
        }

        return $this;
    }

    /**
     * @param   float   $price
     * @param   string  $currency
     * @return  string
     */
    public function formatPrice($price, $currency)
    {
        return Mage::app()->getLocale()->currency($currency)->toCurrency($price);
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * @return  string
     */
    public function getConnectionUrl()
    {
        return $this->getUrl('*/mirakl_seller_connection/edit', array('id' => $this->getConnection()->getId()));
    }

    /**
     * @param   string  $code
     * @return  string
     */
    public function getCountry($code)
    {
        $countries = Mage::app()->getLocale()->getCountryTranslationList();

        return isset($countries[$code]) ? $countries[$code] : $code;
    }

    /**
     * @return  float
     */
    public function getGrandTotal()
    {
        return $this->getMiraklOrder()->getTotalPrice() + $this->getTaxAmount(true);
    }

    /**
     * @return  string
     */
    public function getCancelUrl()
    {
        return $this->getUrl(
            '*/*/cancel', array(
                'connection_id' => $this->getConnection()->getId(),
                'order_id'      => $this->getMiraklOrder()->getId(),
            )
        );
    }

    /**
     * @return  string
     */
    public function getImportUrl()
    {
        return $this->getUrl(
            '*/*/import', array(
                'connection_id' => $this->getConnection()->getId(),
                'order_id'      => $this->getMiraklOrder()->getId(),
            )
        );
    }

    /**
     * @return  string
     */
    public function getRefuseUrl()
    {
        return $this->getUrl(
            '*/*/refuse', array(
                'connection_id' => $this->getConnection()->getId(),
                'order_id'      => $this->getMiraklOrder()->getId(),
            )
        );
    }

    /**
     * @return  Mage_Sales_Model_Order|null
     */
    public function getMagentoOrder()
    {
        return $this->_helper->getOrderByMiraklOrderId($this->getMiraklOrder()->getId());
    }

    /**
     * @return  \Mirakl\MMP\Shop\Domain\Order\ShopOrder
     */
    public function getMiraklOrder()
    {
        return Mage::registry('mirakl_seller_order');
    }

    /**
     * @return  string
     */
    public function getMiraklOrderState()
    {
        return $this->getMiraklOrder()->getStatus()->getState();
    }

    /**
     * @return  string
     */
    public function getMiraklOrderUrl()
    {
        return Mage::helper('mirakl_seller/connection')->getMiraklOrderUrl(
            $this->getConnection(), $this->getMiraklOrder()
        );
    }

    /**
     * The payment duration (i.e. the delay after which the order is supposed to be paid), in days.
     * Only applicable for PAY_ON_DUE_DATE orders.
     * Note that this field has currently no impact on the order workflow, it is just there for information purposes.
     *
     * @return  string|null
     */
    public function getPaymentDuration()
    {
        return $this->getMiraklOrder()->getPaymentDuration() ?: null;
    }

    /**
     * @return  float
     */
    public function getShippingTaxAmount()
    {
        return $this->_helper->getMiraklOrderShippingTaxAmount($this->getMiraklOrder());
    }

    /**
     * @param   bool    $withShipping
     * @return  float
     */
    public function getTaxAmount($withShipping = false)
    {
        return $this->_helper->getMiraklOrderTaxAmount($this->getMiraklOrder(), $withShipping);
    }

    /**
     * @return  bool
     */
    protected function _isOrderInShipping()
    {
        return $this->getMiraklOrderState() === OrderState::SHIPPING;
    }

    /**
     * @return  bool
     */
    protected function _isOrderWaitingAcceptance()
    {
        return $this->getMiraklOrderState() === OrderState::WAITING_ACCEPTANCE;
    }

    /**
     * @return  bool
     */
    protected function _isOrderWaitingDebitPayment()
    {
        return $this->getMiraklOrderState() === OrderState::WAITING_DEBIT_PAYMENT;
    }
}
