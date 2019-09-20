<?php

class MiraklSeller_Sales_Model_Payment_Method_Mirakl extends Mage_Payment_Model_Method_Abstract
{
    /**
     * @var string
     */
    protected $_code = 'mirakl';

    /**
     * {@inheritdoc}
     */
    public function isAvailable($quote = null)
    {
        return $quote && $quote->getFromMiraklOrder();
    }
}