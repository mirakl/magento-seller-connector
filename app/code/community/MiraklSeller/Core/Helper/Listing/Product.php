<?php

use Mage_Catalog_Model_Resource_Eav_Attribute as EavAttribute;
use MiraklSeller_Core_Model_Listing as Listing;

class MiraklSeller_Core_Helper_Listing_Product extends MiraklSeller_Core_Helper_Data
{
    /**
     * @var MiraklSeller_Core_Model_Resource_Product
     */
    protected $_productResource;

    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_productResource = Mage::getResourceSingleton('mirakl_seller/product');
        $this->_offerResource = Mage::getResourceModel('mirakl_seller/offer');
    }

    /**
     * @return  array
     */
    public function getAttributeCodes()
    {
        return $this->_productResource->getExportableAttributeCodes();
    }

    /**
     * @return  Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getAttributesToExport()
    {
        return $this->_productResource->getExportableAttributes();
    }

    /**
     * Retrieve category path (as array) to use for a product that will be exported.
     * Rule is:
     * - take the deepest category
     * - if several categories have the same level, take the first one alphabetically
     *
     * @param   array   $paths
     * @return  array|false
     */
    public function getCategoryFromPaths(array $paths)
    {
        uasort(
            $paths, function ($a, $b) {
                $sortByName = function ($a, $b) {
                    for ($i = count($a) - 1; $i >= 0; $i--) {
                        $compare = strcmp($a[$i], $b[$i]);
                        if (1 === $compare) {
                            return 1;
                        }
                    }

                    return -1;
                };

                return count($a) > count($b) ? -1 : (count($a) < count($b) ? 1 : $sortByName($a, $b));
            }
        );

        return current($paths);
    }

    /**
     * @param   string  $productSku
     * @return  array
     */
    public function getProductAttributeAxis($productSku)
    {
        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $collection */
        $collection = Mage::getResourceModel('eav/entity_attribute_collection');

        $collection->getSelect()
            ->join(
                array('psa' => $collection->getTable('catalog/product_super_attribute')),
                'main_table.attribute_id = psa.attribute_id',
                'product_id'
            )
            ->join(
                array('p' => $collection->getTable('catalog/product')),
                'psa.product_id = p.entity_id',
                'sku'
            )
            ->where('p.sku = ?', $productSku);

        $axisCodes = array();
        foreach ($collection as $attribute) {
            $axisCodes[] = $attribute->getAttributeCode();
        }

        return $axisCodes;
    }

    /**
     * @param   Listing $listing
     * @return  MiraklSeller_Core_Model_Resource_Product_Collection
     */
    public function getProductCollection(Listing $listing)
    {
        $productIds = $listing->getProductIds();

        /** @var MiraklSeller_Core_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('mirakl_seller/product_collection');
        $collection->addFieldToSelect('sku')
            ->addIdFilter($productIds)
            ->setStore($listing->getStoreId());

        return $collection;
    }

    /**
     * @param   Listing $listing
     * @param   array   $productIds
     * @return  array
     */
    public function getProductIdsBySkus(Listing $listing, $productIds = null)
    {
        $collection = Mage::getResourceModel('mirakl_seller/product_collection');
        $collection->addFieldToSelect('sku')
            ->setStore($listing->getStoreId());

        if ($productIds) {
            $collection->addIdFilter($productIds);
        }

        $products = array();
        foreach ($collection as $id => $product) {
            $products[$product['sku']] = $id;
        }

        return $products;
    }

    /**
     * @param   Listing $listing
     * @param   int     $attrChunkSize
     * @return  MiraklSeller_Core_Model_Resource_Product_Collection[]
     */
    public function getProductsDataCollections($listing, $attrChunkSize = 15)
    {
        // Need to split into multipe collections because MySQL has a limited number of join possible for a query
        $collections = array();

        // Working with a limited chunk size because an attribute generates multiple joins
        $attributesChunks = array_chunk($this->getAttributesToExport()->getItems(), $attrChunkSize);

        /** @var EavAttribute[] $attributes */
        foreach ($attributesChunks as $attributes) {
            $collection = $this->getProductCollection($listing);
            foreach ($attributes as $attribute) {
                if ($this->isAttributeUsingOptions($attribute)) {
                    $collection->addAttributeOptionValue($attribute); // Add real option values and not ids
                } else {
                    $collection->addAttributeToSelect($attribute->getAttributeCode());
                }
            }

            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeUsingOptions(EavAttribute $attribute)
    {
        $model = Mage::getModel($attribute->getSourceModel());
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
            (
                ($backend == 'int' && $model instanceof Mage_Eav_Model_Entity_Attribute_Source_Table) ||
                ($backend == 'varchar' && $attribute->getFrontendInput() == 'multiselect')
            );
    }

    /**
     * Marks failed products as new if failure delay has expired and returns the number of updated products
     *
     * @param   Listing $listing
     * @return  int
     */
    public function processFailedProducts(Listing $listing)
    {
        $delay = Mage::helper('mirakl_seller/config')->getNbDaysKeepFailedProducts();
        $productIds = $this->_offerResource->getListingFailedProductIds($listing->getId(), $delay);

        return $this->_offerResource->markProductsAsNew($listing->getId(), $productIds);
    }
}
