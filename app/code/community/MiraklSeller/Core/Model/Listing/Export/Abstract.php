<?php

use MiraklSeller_Core_Model_Listing as Listing;

abstract class MiraklSeller_Core_Model_Listing_Export_Abstract
    implements MiraklSeller_Core_Model_Listing_Export_Interface
{
    /**
     * @var MiraklSeller_Core_Helper_Listing_Product
     */
    protected $_productHelper;

    /**
     * @var MiraklSeller_Core_Model_Listing_Export_Formatter_Interface
     */
    protected $_offerFormatter;

    /**
     * @var MiraklSeller_Core_Model_Listing_Export_Formatter_Interface
     */
    protected $_productFormatter;

    /**
     * @var MiraklSeller_Core_Model_Listing_Export_AdditionalField_Formatter
     */
    protected $_additionalFieldFormatter;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_productHelper            = Mage::helper('mirakl_seller/listing_product');
        $this->_offerFormatter           = Mage::getModel('mirakl_seller/listing_export_formatter_offer');
        $this->_productFormatter         = Mage::getModel('mirakl_seller/listing_export_formatter_product');
        $this->_additionalFieldFormatter = Mage::getModel('mirakl_seller/listing_export_additionalField_formatter');
    }

    /**
     * @param   null|string $value
     * @return  array
     */
    public function getDefaultProductData($value = null)
    {
        return array_fill_keys($this->_productHelper->getAttributeCodes(), $value);
    }

    /**
     * @param   Listing $listing
     * @return  array
     */
    public function getListingProductsData(Listing $listing)
    {
        $collections = $this->_productHelper->getProductsDataCollections($listing);

        $data = array();
        /** @var MiraklSeller_Core_Model_Resource_Product_Collection $collection */
        foreach ($collections as $collection) {
            foreach ($collection as $product) {
                $productId = $product['entity_id'];
                if (!isset($data[$productId])) {
                    $data[$productId] = array();
                }

                $data[$productId] += $product;
            }
        }

        return $data;
    }

    /**
     * Prepares offer data for export
     *
     * @param   array   $product
     * @param   Listing $listing
     * @param   string  $action
     * @return  array
     */
    public function prepareOffer(array $product, Listing $listing, $action = 'update')
    {
        $product['action'] = $action;
        $product['state'] = $listing->getOfferState();

        $product = $this->handleProductReferenceIdentifiers($product, $listing);
        $additionalFields = $this->handleAdditionalFields($product, $listing);
        $product = $this->_offerFormatter->format($product, $listing);

        return array_merge($product, $additionalFields);
    }

    /**
     * Handles offer additional fields
     *
     * @param   array   $product
     * @param   Listing $listing
     * @return  array
     */
    public function handleAdditionalFields(array $product, Listing $listing)
    {
        $data = array();
        $fields = $listing->getOfferAdditionalFields();
        $values = $listing->getOfferAdditionalFieldsValues();
        $defaultValues = isset($values['defaults']) ? $values['defaults'] : array();
        $attrCodes = isset($values['attributes']) ? $values['attributes'] : array();

        foreach ($fields as $field) {
            $key = $field['code']; // Additional field code

            // Initialize default value and optional Magento attribute code mapping
            $defaultValue = isset($defaultValues[$key]) ? $defaultValues[$key] : '';
            $attrCode = isset($attrCodes[$key]) ? $attrCodes[$key] : '';

            // Init additional field with default value, even if empty
            $value = $defaultValue;

            // If Magento attribute is specified AND has a value then use it.
            // If Magento attribute is specified AND has an empty value then allow empty only if field is not required.
            if ($attrCode && (!empty($product[$attrCode]) || !$field['required'])) {
                $value = isset($product[$attrCode]) ? $product[$attrCode] : '';
            }

            $data[$key] = $this->_additionalFieldFormatter->format($field, $value);
        }

        return $data;
    }

    /**
     * Handles product reference identifiers
     *
     * @param   array   $product
     * @param   Listing $listing
     * @return  array
     */
    public function handleProductReferenceIdentifiers(array $product, Listing $listing)
    {
        // Handle product reference identifiers
        $productIdValueAttribute = $listing->getProductIdValueAttribute();
        $productIdType = $listing->getProductIdType();

        $product['product-id'] = !empty($productIdValueAttribute) && isset($product[$productIdValueAttribute])
            ? $product[$productIdValueAttribute]
            : $product['sku'];

        $product['product-id-type'] = !empty($productIdType)
            ? $productIdType
            : MiraklSeller_Core_Model_Listing_Export_Formatter_Offer::DEFAULT_PRODUCT_ID_TYPE;

        return $product;
    }
}