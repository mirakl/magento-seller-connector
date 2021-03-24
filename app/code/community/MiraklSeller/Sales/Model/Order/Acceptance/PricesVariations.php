<?php

class MiraklSeller_Sales_Model_Order_Acceptance_PricesVariations
{
    /**
     * @return  int|null
     */
    public function getConfig()
    {
        return Mage::helper('mirakl_seller_sales/config')->getPricesVariationsPercent();
    }

    /**
     * Returns true if price variation between Magento and Mirakl is valid according to config
     *
     * @param   float   $magentoPrice
     * @param   float   $miraklPrice
     * @return  bool
     */
    public function isPriceVariationValid($magentoPrice, $miraklPrice)
    {
        $percent = $this->getConfig();

        if (null === $percent) {
            return true;
        }

        return $miraklPrice >= ($magentoPrice * (1 - $percent / 100));
    }
}