<?php

class MiraklSeller_Core_Helper_Config extends MiraklSeller_Core_Helper_Data
{
    const XML_PATH_AUTO_CREATE_TRACKING                         = 'mirakl_seller/listing/auto_create_tracking';
    const XML_PATH_NUMBER_IMAGE_EXPORT                          = 'mirakl_seller/listing/nb_image_exported';
    const XML_PATH_NB_DAYS_EXPIRED                              = 'mirakl_seller/listing/nb_days_expired';
    const XML_PATH_NB_DAYS_KEEP_FAILED_PRODUCTS                 = 'mirakl_seller/listing/nb_days_keep_failed_products';
    const XML_PATH_DISCOUNT_ENABLE_PROMOTION_CATALOG_PRICE_RULE = 'mirakl_seller/prices/enable_promotion_catalog_price_rule';
    const XML_PATH_DISCOUNT_CUSTOMER_GROUP                      = 'mirakl_seller/prices/customer_group';

    /**
     * Returns store locale
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getLocale($store = null)
    {
        return Mage::getStoreConfig('general/locale/code', $store);
    }

    /**
     * Returns store name if defined
     *
     * @param   mixed   $store
     * @return  string
     */
    public function getStoreName($store = null)
    {
        return Mage::getStoreConfig(Mage_Core_Model_Store::XML_PATH_STORE_STORE_NAME, $store);
    }

    /**
     * Returns true if we need to create a listing tracking after each products export
     *
     * @return  bool
     */
    public function isAutoCreateTracking()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUTO_CREATE_TRACKING);
    }

    /**
     * Returns number of days after which the products will be expired
     *
     * @param   int $default
     * @return  int
     */
    public function getNbDaysExpired($default = 10)
    {
        $days = (int) Mage::getStoreConfig(self::XML_PATH_NB_DAYS_EXPIRED);

        return $days > 0 ? $days : $default;
    }

    /**
     * Returns number of days after which a failed product will be exported again
     *
     * @param   int $default
     * @return  int
     */
    public function getNbDaysKeepFailedProducts($default = 10)
    {
        $days = (int) Mage::getStoreConfig(self::XML_PATH_NB_DAYS_KEEP_FAILED_PRODUCTS);

        return $days >= 0 ? $days : $default;
    }

    /**
     * Returns all attribute mapping for offer additional attribute
     *
     * @param   mixed   $store
     * @return  array
     */
    public function getOfferFieldsMapping($store = null)
    {
        return Mage::getStoreConfig('mirakl_seller/offer_fields_mapping', $store);
    }

    /**
     * Returns number of images to export
     *
     * @return  int
     */
    public function getNumberImageMaxToExport()
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_NUMBER_IMAGE_EXPORT);
    }

    /**
     * If true, exported discount price will be populated with the best price between applicable Magento promotion catalog price rules and Magento special price.
     *
     * @return  bool
     */
    public function isPromotionPriceExported()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DISCOUNT_ENABLE_PROMOTION_CATALOG_PRICE_RULE);
    }

    /**
     * Get the customer group to use for prices and qty export
     *
     * @return  int
     */
    public function getCustomerGroup()
    {
        return Mage::getStoreConfig(self::XML_PATH_DISCOUNT_CUSTOMER_GROUP);
    }
}
