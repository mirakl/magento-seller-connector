<?php

use MiraklSeller_Core_Model_Offer as Offer;

class MiraklSeller_Core_Model_Listing_Export_Offers extends MiraklSeller_Core_Model_Listing_Export_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function export(MiraklSeller_Core_Model_Listing $listing)
    {
        /** @var MiraklSeller_Core_Helper_Config $config */
        $config = Mage::helper('mirakl_seller/config');
        $attributeHelper = Mage::getSingleton('eav/config');

        $collection = $this->_productHelper->getProductCollection($listing);
        $collection->addTierPricesToSelect($listing->getWebsiteId(), $config->getCustomerGroup())
            ->addListingPriceData($listing)
            ->addQuantityToSelect()
            ->addAttributeToSelect(array('description', 'special_price', 'special_from_date', 'special_to_date'));

        $connection = $listing->getConnection();
        if ($connection->getExportedPricesAttribute()) {
            $collection->addAttributeToSelect($connection->getExportedPricesAttribute());
        }

        // Add mapped attributes to select
        foreach ($config->getOfferFieldsMapping($listing->getStoreId()) as $value) {
            if ($value) {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
                $attribute = $attributeHelper->getAttribute(
                    Mage_Catalog_Model_Product::ENTITY,
                    $value
                );

                if ($collection->isAttributeUsingOptions($attribute)) {
                    $collection->addAttributeOptionValue($attribute);
                } else {
                    $collection->addAttributeToSelect($value);
                }
            }
        }

        // Add attribute corresponding to product-id if not setup as sku
        if (($productIdValueAttribute = $listing->getProductIdValueAttribute()) != 'sku') {
            $collection->addAttributeToSelect($productIdValueAttribute);
        }

        // Add potential attributes associated with offer additional fields
        $collection->addAdditionalFieldsAttributes($listing);

        /** @var MiraklSeller_Core_Model_Resource_Offer $offerResource */
        $offerResource = Mage::getResourceModel('mirakl_seller/offer');
        $offerResource->addOfferInfoToProductCollection($listing->getId(), $collection, array('offer_import_status'));

        $collection->load(); // Load collection to be able to use methods below
        $collection->overrideByParentData($listing, array(), array(), true, true);
        $collection->addConfigurableAdditionalPrice();

        $data = array();
        foreach ($collection as $product) {
            $productId = $product['entity_id'];
            if ($product['offer_import_status'] == Offer::OFFER_DELETE) {
                $product['qty'] = 0; // Set quantity to zero if offer has been flagged as "to delete"
            }
            $data[$productId] = $this->prepareOffer($product, $listing);
        }

        // Mark out of stock products that are not in the export (out of stock, no price)
        $deleteIds = array_diff($listing->getProductIds(), array_keys($data));
        if (count($deleteIds)) {
            /** @var MiraklSeller_Core_Model_Resource_Product_Collection $collection */
            $collection = Mage::getResourceModel('mirakl_seller/product_collection');
            $collection->addFieldToSelect('sku')
                ->setStore($listing->getStoreId())
                ->addIdFilter($deleteIds);

            foreach ($collection as $product) {
                $productId = $product['entity_id'];
                $product['qty'] = 0; // Set quantity to zero, do not delete the offer in Mirakl
                $data[$productId] = $this->prepareOffer($product, $listing);
            }
        }

        return $data;
    }
}