<?php

use MiraklSeller_Core_Model_Listing_Export_Formatter_Product as ProductFormatter;

class MiraklSeller_Core_Model_Listing_Export_Products extends MiraklSeller_Core_Model_Listing_Export_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function export(MiraklSeller_Core_Model_Listing $listing)
    {
        $data = $this->getListingProductsData($listing);

        $collection = $this->_productHelper->getProductCollection($listing);
        $collection->load(); // Load collection to be able to use methods below
        $collection->addCategoryPaths();

        $exportableAttributes = $listing->getConnection()->getExportableAttributes();
        $collection->overrideByParentData($listing, array('parent_sku' => 'sku'), $exportableAttributes);

        $nbImageToExport = Mage::helper('mirakl_seller/config')->getNumberImageMaxToExport();
        $variantsAttributes = $listing->getVariantsAttributes();

        $defaultProductData = $this->getDefaultProductData();

        if ($nbImageToExport >= 1) {
            $collection->addMediaGalleryAttribute($nbImageToExport);
        }

        foreach ($collection as $product) {
            $productId = $product['entity_id'];
            $data[$productId] = array_merge(
                $defaultProductData,
                $data[$productId],
                $this->_productFormatter->format($product, $listing)
            );

            // Extend parent code for specific listings
            if (!empty($data[$productId][ProductFormatter::VARIANT_GROUP_CODE_FIELD]) && count($variantsAttributes)) {
                $parentSku = $data[$productId][ProductFormatter::VARIANT_GROUP_CODE_FIELD];
                $productAxis = Mage::helper('mirakl_seller/listing_product')
                    ->getProductAttributeAxis($parentSku);

                foreach ($variantsAttributes as $attributeCode) {
                    if (in_array($attributeCode, $productAxis)) {
                        $parentSku = sprintf(
                            '%s-%s',
                            $parentSku,
                            $data[$productId][$attributeCode]
                        );
                    }
                }

                $data[$productId][ProductFormatter::VARIANT_GROUP_CODE_FIELD] = $parentSku;
            }
        }

        return $data;
    }
}