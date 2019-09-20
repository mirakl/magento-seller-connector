<?php

use MiraklSeller_Core_Model_Listing as Listing;

class MiraklSeller_Core_Model_Listing_Export_Formatter_Product
    implements MiraklSeller_Core_Model_Listing_Export_Formatter_Interface
{
    /**
     * Custom field in order to allow seller to map this field with the variant group code field in Mirakl
     */
    const VARIANT_GROUP_CODE_FIELD = 'variant_group_code';
    const CATEGORY_FIELD = 'category';
    const IMAGE_FIELD = 'image_';

    /**
     * {@inheritdoc}
     */
    public function format(array $data, Listing $listing)
    {
        $category = '';
        if (isset($data['category_paths']) && !empty($data['category_paths'])) {
            $path = Mage::helper('mirakl_seller/listing_product')
                ->getCategoryFromPaths($data['category_paths']);
            $category = implode('/', str_replace('/', '-', $path));
        }

        $parentSku = '';
        if (isset($data['parent_sku'])) {
            $parentSku = $data['parent_sku'];
        }

        $formatData = array_intersect_key(
            $data,
            array_fill_keys($listing->getConnection()->getExportableAttributes(), null)
        );

        $nbImageToExport = Mage::helper('mirakl_seller/config')->getNumberImageMaxToExport();

        // We must add the column key definition for array_merge
        for ($i = 0; $i < $nbImageToExport; $i++) {
            $formatData[self::IMAGE_FIELD . ($i + 1)] = '';
        }

        foreach ($data as $key => $item) {
            if (strstr($key, self::IMAGE_FIELD) !== false) {
                $formatData[$key] = $data[$key];
            }
        }

        $formatData[self::CATEGORY_FIELD] = $category;
        $formatData[self::VARIANT_GROUP_CODE_FIELD] = $parentSku;

        return $formatData;
    }
}