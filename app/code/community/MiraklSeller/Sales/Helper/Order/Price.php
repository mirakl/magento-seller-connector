<?php

use Mage_Catalog_Model_Product as Product;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Sales_Helper_Order_Price extends Mage_Core_Helper_Abstract
{
    /**
     * @param   Product     $product
     * @param   Connection  $connection
     * @param   null|int    $qty
     * @return  float
     */
    public function getMagentoPrice(Product $product, Connection $connection, $qty = null)
    {
        // Check if custom price is available on the product
        if ($connection->getExportedPricesAttribute()) {
            $magentoPrice = $product->getData($connection->getExportedPricesAttribute());
            if (!empty($magentoPrice)) {
                return $magentoPrice;
            }
        }

        // Check if a discount price is available on the product
        $magentoPrice = $this->getDiscountPrice($product, $connection->getStoreId());
        if (!empty($magentoPrice)) {
            return $magentoPrice;
        }

        return $product->getFinalPrice($qty);
    }

    /**
     * @param   Product $product
     * @param   mixed   $store
     * @return  float|null
     */
    public function getDiscountPrice(Product $product, $store = null)
    {
        $store = $store ? Mage::app()->getStore($store) : Mage::app()->getDefaultStoreView();
        $product->setStoreId($store->getId());
        $product->setCustomerGroupId($this->getCustomerGroupId());

        // Check if a discount price is available on the product
        $catalogRule = Mage::getModel('catalogrule/rule');

        return $catalogRule->calcProductPriceRule($product, $product->getPrice());
    }

    /**
     * @return  int
     */
    public function getCustomerGroupId()
    {
        return Mage::helper('mirakl_seller/config')->getCustomerGroup();
    }
}