<?php
namespace Mirakl\Catalog;

trait ProductLoaderTrait
{
    /**
     * @param   string  $sku
     * @return  \Mage_Catalog_Model_Product
     */
    public function loadProductBySku($sku)
    {
        /** @var \Mage_Catalog_Model_Product $product */
        $product = \Mage::getModel('catalog/product');
        $product->load($product->getIdBySku($sku));

        return $product;
    }
}