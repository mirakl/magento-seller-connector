<?php

class MiraklSeller_Sales_Model_Order_Acceptance_Backorder
{
    const ACCEPT_ITEM_AUTOMATICALLY  = 1;
    const MANAGE_ORDER_MANUALLY      = 2;
    const REJECT_ITEM_AUTOMATICALLY  = 3;

    /**
     * @var array
     */
    protected static $options = array(
        self::ACCEPT_ITEM_AUTOMATICALLY  => 'Accept item automatically',
        self::MANAGE_ORDER_MANUALLY      => 'Manage order manually',
        self::REJECT_ITEM_AUTOMATICALLY  => 'Reject item automatically',
    );

    /**
     * @return  int
     */
    public function getConfig()
    {
        return Mage::helper('mirakl_seller_sales/config')->getBackorderBehavior();
    }

    /**
     * @return  array
     */
    public static function getOptions()
    {
        return static::$options;
    }

    /**
     * @return  bool
     */
    public function isAcceptItemAutomatically()
    {
        return $this->getConfig() === self::ACCEPT_ITEM_AUTOMATICALLY;
    }

    /**
     * @return  bool
     */
    public function isManageOrderManually()
    {
        return $this->getConfig() === self::MANAGE_ORDER_MANUALLY;
    }

    /**
     * @return  bool
     */
    public function isRejectItemAutomatically()
    {
        return $this->getConfig() === self::REJECT_ITEM_AUTOMATICALLY;
    }

    /**
     * @return  array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach (static::getOptions() as $code => $label) {
            $options[] = array(
                'value' => $code,
                'label' => Mage::helper('mirakl_seller_sales')->__($label),
            );
        }

        return $options;
    }
}