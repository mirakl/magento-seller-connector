<?php

class MiraklSeller_Core_Model_Rule extends Mage_CatalogRule_Model_Rule
{
    /**
     * Store websites map for better performances when looping on very large catalog
     *
     * @var array
     */
    protected $_websitesMap;

    /**
     * Getter for rule conditions collection
     *
     * @return Mage_CatalogRule_Model_Rule_Condition_Combine
     */
    public function getConditionsInstance()
    {
        return Mage::getModel('mirakl_seller/rule_condition_combine');
    }

    /**
     * Override default method in order to cache websites mapping
     *
     * @return array
     */
    protected function _getWebsitesMap()
    {
        if (null === $this->_websitesMap) {
            foreach (Mage::app()->getWebsites(true) as $website) {
                /** @var $website Mage_Core_Model_Website */
                if ($website->getDefaultStore()) {
                    $this->_websitesMap[$website->getId()] = $website->getDefaultStore()->getId();
                }
            }
        }

        return $this->_websitesMap;
    }

    /**
     * Do not use resource iterator which can be slow
     *
     * @return array
     */
    public function getMatchingProductIds()
    {
        if (null === $this->_productIds) {
            $this->_productIds = array();

            if ($this->getWebsiteIds()) {
                /** @var $collection Mage_Catalog_Model_Resource_Product_Collection */
                $collection = Mage::getResourceModel('catalog/product_collection');
                $collection->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);
                $collection->addWebsiteFilter($this->getWebsiteIds());
                if ($this->_productsFilter) {
                    $collection->addIdFilter($this->_productsFilter);
                }

                $this->getConditions()->collectValidatedAttributes($collection);

                $rows = Mage::getSingleton('core/resource')
                    ->getConnection('core_read')
                    ->fetchAll($collection->getSelect()->distinct(false));

                $product = Mage::getModel('catalog/product');
                $conds = $this->getConditions();
                foreach ($rows as $k => $row) {
                    $product = clone $product;
                    $product->setData($row);

                    $results = array();
                    foreach ($this->_getWebsitesMap() as $websiteId => $defaultStoreId) {
                        $product->setData('store_id', $defaultStoreId);
                        $results[$websiteId] = (int) $conds->validate($product);
                    }

                    $productId = $row['entity_id'];
                    if (isset($this->_productIds[$productId])) {
                        $results = array_merge($this->_productIds[$productId], $results);
                    }

                    $this->_productIds[$productId] = $results;
                }
            }
        }

        return $this->_productIds;
    }
}
