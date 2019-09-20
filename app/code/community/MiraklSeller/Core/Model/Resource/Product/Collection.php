<?php

use Mage_Catalog_Model_Resource_Eav_Attribute as EavAttribute;
use MiraklSeller_Core_Model_Listing as Listing;

/**
 * /!\ This is not an override of the default Magento product collection but just an extension
 * in order to manipulate collection items as arrays instead of product objects for better performances.
 *
 * @method $this addAttributeToSelect($field, $condition = null)
 * @method $this addFieldToFilter($field, $condition = null)
 * @property Varien_Db_Adapter_Interface $_conn
 */
class MiraklSeller_Core_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    // Avoid very large IN() clause in MySQL queries and use join instead on a temp table
    const MAX_PRODUCT_IDS_IN_WHERE = 1000;

    /**
     * @param   Listing $listing
     * @return  $this
     */
    public function addAdditionalFieldsAttributes(Listing $listing)
    {
        $additionalFieldsValues = $listing->getOfferAdditionalFieldsValues();
        $attrCodes = isset($additionalFieldsValues['attributes']) ? $additionalFieldsValues['attributes'] : array();
        $attrCodes = array_unique(array_filter($attrCodes));
        foreach ($attrCodes as $attrCode) {
            $this->addAttribute($attrCode);
        }

        return $this;
    }

    /**
     * @param   string  $attributeCode
     * @return  $this
     */
    public function addAttribute($attributeCode)
    {
        if ($attribute = Mage::getResourceSingleton('catalog/product')->getAttribute($attributeCode)) {
            if ($this->isAttributeUsingOptions($attribute)) {
                $this->addAttributeOptionValue($attribute);
            } else {
                $this->addAttributeToSelect($attributeCode);
            }
        }

        return $this;
    }

    /**
     * @param   EavAttribute    $attribute
     * @return  $this
     */
    public function addAttributeOptionValue(EavAttribute $attribute)
    {
        if (!$this->isAttributeUsingOptions($attribute)) {
            return $this;
        }

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        $attributeCode = $attribute->getAttributeCode();

        $valueTableA = $attributeCode . '_t1';
        $valueTableB = $attributeCode . '_t2';
        $this->getSelect()
            ->joinLeft(
                array($valueTableA => $attribute->getBackend()->getTable()),
                "e.entity_id = {$valueTableA}.entity_id"
                . " AND {$valueTableA}.attribute_id = {$attribute->getId()}"
                . " AND {$valueTableA}.store_id = 0",
                array()
            )
            ->joinLeft(
                array($valueTableB => $attribute->getBackend()->getTable()),
                "e.entity_id = {$valueTableB}.entity_id"
                . " AND {$valueTableB}.attribute_id = {$attribute->getId()}"
                . " AND {$valueTableB}.store_id = {$storeId}",
                array()
            );

        $valueExpr = $this->_conn->getCheckSql(
            "{$valueTableB}.value_id > 0",
            "{$valueTableB}.value",
            "{$valueTableA}.value"
        );

        $optionTableA   = $attributeCode . '_option_value_t1';
        $optionTableB   = $attributeCode . '_option_value_t2';
        $tableJoinCondA = "{$optionTableA}.option_id = {$valueExpr} AND {$optionTableA}.store_id = 0";
        $tableJoinCondB = "{$optionTableB}.option_id = {$valueExpr} AND {$optionTableB}.store_id = {$storeId}";
        $valueExpr      = $this->_conn->getCheckSql(
            "{$optionTableB}.value_id IS NULL",
            "{$optionTableA}.value",
            "{$optionTableB}.value"
        );

        $this->getSelect()
            ->joinLeft(
                array($optionTableA => $this->getTable('eav/attribute_option_value')),
                $tableJoinCondA,
                array()
            )
            ->joinLeft(
                array($optionTableB => $this->getTable('eav/attribute_option_value')),
                $tableJoinCondB,
                array($attributeCode => $valueExpr)
            );

        return $this;
    }

    /**
     * Add category ids to loaded items
     *
     * @param   bool    $fallbackToParent
     * @return  $this
     */
    public function addCategoryIds($fallbackToParent = true)
    {
        if ($this->getFlag('category_ids_added')) {
            return $this;
        }

        $productIds = array_keys($this->_items);
        if (empty($productIds)) {
            return $this;
        }

        $productCategoryIds = $this->getProductCategoryIds($productIds);

        $productsWithCategories = array();
        foreach ($productCategoryIds as $productId => $categoryIds) {
            $productsWithCategories[$productId] = true;
            $this->_items[$productId]['category_ids'] = $categoryIds;
        }

        if ($fallbackToParent) {
            // Search for categories associated to parent product if possible
            $productsWithoutCategories = array_diff_key($this->_items, $productsWithCategories);
            $parentProductIds = $this->_getParentProductIds(array_keys($productsWithoutCategories));
            if (!empty($parentProductIds)) {
                $parentIds = array();
                foreach ($parentProductIds as $ids) {
                    $parentIds = array_merge($parentIds, $ids);
                }

                $parentIds = array_unique($parentIds);
                $parentProductCategoryIds = $this->getProductCategoryIds($parentIds);
                foreach ($parentProductIds as $productId => $parentIds) {
                    foreach ($parentIds as $parentId) {
                        if (isset($parentProductCategoryIds[$parentId])) {
                            $this->_items[$productId]['category_ids'] = $parentProductCategoryIds[$parentId];
                            continue 2; // skip this product as soon as we have found some categories for it
                        }
                    }
                }
            }
        }

        $this->setFlag('category_ids_added', true);

        return $this;
    }

    /**
     * Add category names to loaded items
     *
     * @return  $this
     */
    public function addCategoryNames()
    {
        if ($this->getFlag('category_names_added')) {
            return $this;
        }

        $productIds = array_keys($this->_items);
        if (empty($productIds)) {
            return $this;
        }

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        /** @var EavAttribute $attribute */
        $attribute = Mage::getResourceModel('catalog/category')->getAttribute('name');

        $colsExprSql = array(
            'product_id',
            'name' => $this->_conn->getIfNullSql('category_name_t2.value', 'category_name_t1.value')
        );
        $select = $this->_conn
            ->select()
            ->from(array('category_product' =>  $this->_productCategoryTable), $colsExprSql)
            ->joinLeft(
                array('category_name_t1' => $attribute->getBackend()->getTable()),
                "category_product.category_id = category_name_t1.entity_id"
                . " AND category_name_t1.attribute_id = {$attribute->getId()}"
                . " AND category_name_t1.store_id = 0",
                array()
            )
            ->joinLeft(
                array('category_name_t2' => $attribute->getBackend()->getTable()),
                "category_product.category_id = category_name_t2.entity_id"
                . " AND category_name_t2.attribute_id = {$attribute->getId()}"
                . " AND category_name_t2.store_id = {$storeId}",
                array()
            )
            ->where('category_product.product_id IN (?)', $productIds);

        $data = $this->_conn->fetchAll($select);

        foreach ($data as $info) {
            $productId = $info['product_id'];
            if (!isset($this->_items[$productId]['category_names'])) {
                $this->_items[$productId]['category_names'] = array();
            }

            if (null !== $info['name']) {
                $this->_items[$productId]['category_names'][] = $info['name'];
            }
        }

        $this->setFlag('category_names_added', true);

        return $this;
    }

    /**
     * Add category paths to loaded items
     *
     * @return  $this
     */
    public function addCategoryPaths()
    {
        if ($this->getFlag('category_paths_added') || empty($this->_items)) {
            return $this;
        }

        // Category ids are required
        $this->addCategoryIds();

        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        /** @var EavAttribute $attribute */
        $attribute = Mage::getResourceModel('catalog/category')->getAttribute('name');

        $colsExprSql = array(
            'category_id' => 'categories.entity_id',
            'path' => 'categories.path',
            'name' => $this->_conn->getIfNullSql('category_name_t2.value', 'category_name_t1.value')
        );
        $select = $this->_conn
            ->select()
            ->from(array('categories' => $this->getTable('catalog/category')), $colsExprSql)
            ->joinLeft(
                array('category_name_t1' => $attribute->getBackend()->getTable()),
                "categories.entity_id = category_name_t1.entity_id"
                . " AND category_name_t1.attribute_id = {$attribute->getId()}"
                . " AND category_name_t1.store_id = 0",
                array()
            )
            ->joinLeft(
                array('category_name_t2' => $attribute->getBackend()->getTable()),
                "categories.entity_id = category_name_t2.entity_id"
                . " AND category_name_t2.attribute_id = {$attribute->getId()}"
                . " AND category_name_t2.store_id = {$storeId}",
                array()
            );

        $categories = $this->_conn->fetchAssoc($select);

        $getCategoryPath = function ($categoryId) use ($categories) {
            $pathNames = array();
            if (isset($categories[$categoryId])) {
                $pathCategoryIds = explode('/', $categories[$categoryId]['path']);
                foreach ($pathCategoryIds as $pathCategoryId) {
                    if ($pathCategoryId > 1 && isset($categories[$pathCategoryId])) {
                        $pathNames[] = $categories[$pathCategoryId]['name'];
                    }
                }
            }

            return $pathNames;
        };

        foreach ($this->_items as $productId => $data) {
            $this->_items[$productId]['category_paths'] = array();
            if (isset($data['category_ids']) && is_array($data['category_ids'])) {
                foreach ($data['category_ids'] as $categoryId) {
                    $this->_items[$productId]['category_paths'][$categoryId] = $getCategoryPath($categoryId);
                }
            }
        }

        $this->setFlag('category_paths_added', true);

        return $this;
    }

    /**
     * @return  $this
     */
    public function addConfigurableAdditionalPrice()
    {
        if ($this->getFlag('configurable_additional_price_added') || empty($this->_items)) {
            return $this;
        }

        $productIds = array_keys($this->_items);

        $select = $this->_conn
            ->select()
            ->from(array('e' => $this->getEntity()->getEntityTable()), 'entity_id')
            ->joinLeft(
                array('link' => $this->getTable('catalog/product_super_link')),
                'link.product_id = e.entity_id',
                array('parent_id')
            )
            ->joinLeft(
                array('attribute' => $this->getTable('catalog_product_entity_int')),
                'attribute.entity_id = e.entity_id',
                array()
            )
            ->joinLeft(
                array('psa' => $this->getTable('catalog/product_super_attribute')),
                'psa.product_id = link.parent_id AND psa.attribute_id = attribute.attribute_id',
                array()
            )
            ->joinLeft(
                array('psap' => $this->getTable('catalog/product_super_attribute_pricing')),
                'psap.product_super_attribute_id = psa.product_super_attribute_id'  .
                    ' AND psap.value_index = attribute.value',
                array('is_percent', 'pricing_value')
            )
            ->where('e.entity_id IN (?)', $productIds)
            ->where('psap.pricing_value IS NOT NULL');

        $data = $this->_conn->fetchAll($select);
        foreach ($data as $info) {
            $productId = $info['entity_id'];

            if (!isset($this->_items[$productId]['parent_id']) ||
                    $this->_items[$productId]['parent_id'] != $info['parent_id']) {
                // The product is not linked to a configurable product or link with 2 configurables
                continue;
            }

            if ($info['pricing_value']) {
                if (!isset($this->_items[$productId]['additional_price'])) {
                    $this->_items[$productId]['additional_price'] = 0;
                }

                if ($info['is_percent']) {
                    $ratio = $info['pricing_value'] / 100;
                    $price = $this->_items[$productId]['price'] * $ratio;
                } else {
                    $price = $info['pricing_value'];
                }

                $this->_items[$productId]['additional_price'] += $price;
            }
        }

        $this->setFlag('configurable_additional_price_added', true);

        return $this;
    }

    /**
     * @param   string  $field
     * @return  $this
     */
    public function addFieldToSelect($field)
    {
        $this->getSelect()->columns($field);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addIdFilter($productId, $exclude = false)
    {
        if (!is_array($productId) || count($productId) < static::MAX_PRODUCT_IDS_IN_WHERE) {
            return parent::addIdFilter($productId, $exclude);
        }

        // Handle large product ids data in a temporary table to avoid big IN() clause that is slow
        $tmpTableName = $this->_createTempTableWithProductIds($productId);
        $this->getSelect()->join(
            array('tmp_products' => $this->getTable($tmpTableName)),
            self::MAIN_TABLE_ALIAS . '.entity_id = tmp_products.product_id',
            ''
        );

        return $this;
    }

    /**
     * @param   Listing $listing
     * @return  $this
     */
    public function addListingPriceData(Listing $listing)
    {
        Mage::helper('mirakl_seller/listing')->addListingPriceDataToCollection($listing, $this);

        return $this;
    }

    /**
     * Add image URL to loaded items
     *
     * @param   int $nbImage
     * @return  $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function addMediaGalleryAttribute($nbImage = 1)
    {
        $productIds = array_keys($this->_items);

        if (empty($productIds) || $this->getFlag('images_url_added')) {
            return $this;
        }

        // Retrieve products images
        $productImages = $this->getProductImages($productIds);

        // Retrieve parent product images for products without image associated
        $productsWithoutImages = array_diff_key($this->_items, $productImages);
        if (!empty($productsWithoutImages)) {
            $parentProductIds = $this->_getParentProductIds(array_keys($productsWithoutImages));
            if (!empty($parentProductIds)) {
                $parentIds = array();
                foreach ($parentProductIds as $ids) {
                    $parentIds = array_merge($parentIds, $ids);
                }

                $parentIds = array_unique($parentIds);
                $parentProductImages = $this->getProductImages($parentIds);
                foreach ($parentProductIds as $productId => $parentIds) {
                    foreach ($parentIds as $parentId) {
                        if (isset($parentProductImages[$parentId])) {
                            $productImages[$productId] = $parentProductImages[$parentId];
                            continue 2; // skip this product as soon as we have found some images for it
                        }
                    }
                }
            }
        }

        foreach ($productImages as $productId => $images) {
            foreach ($images as $i => $image) {
                if ($nbImage <= $i) {
                    break;
                }

                $imageKey = MiraklSeller_Core_Model_Listing_Export_Formatter_Product::IMAGE_FIELD . ($i + 1);
                $this->_items[$productId][$imageKey] = $this->getMediaConfig()->getMediaUrl($image['file']);
            }
        }

        unset($productImages);

        $this->setFlag('images_url_added', true);

        return $this;
    }

    /**
     * @param   int $websiteId
     * @param   int $groupId
     * @return  $this
     */
    public function addTierPricesToSelect($websiteId, $groupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)
    {
        if ($this->getFlag('tier_prices_added')) {
            return $this;
        }

        $tierPricesSql = new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT CONCAT_WS('|', FLOOR(tier_prices.qty), ROUND(tier_prices.value, 2)) SEPARATOR ',')");
        $this->getSelect()
            ->joinLeft(
                array('tier_prices' => $this->getTable('catalog/product_attribute_tier_price')),
                sprintf(
                    'e.entity_id = tier_prices.entity_id AND (website_id = %d OR website_id = 0) AND (customer_group_id = %d OR all_groups = 1)',
                    $websiteId,
                    $groupId
                ),
                array('tier_prices' => $tierPricesSql)
            )
            ->group('e.entity_id');

        $this->setFlag('tier_prices_added', true);

        return $this;
    }

    /**
     * @return  $this
     */
    public function addQuantityToSelect()
    {
        if ($this->getFlag('qty_added')) {
            return $this;
        }

        $this->joinTable(
            'cataloginventory/stock_item',
            'product_id = entity_id',
            array('qty', 'use_config_min_sale_qty', 'min_sale_qty', 'use_config_max_sale_qty',
                'max_sale_qty','use_config_enable_qty_inc', 'enable_qty_increments',
                'use_config_qty_increments', 'qty_increments'),
            '{{table}}.stock_id = 1',
            'left'
        );

        $this->setFlag('qty_added', true);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getLoadAttributesSelect($table, $attributeIds = array())
    {
        if (count($this->_itemsById) < static::MAX_PRODUCT_IDS_IN_WHERE) {
            return parent::_getLoadAttributesSelect($table, $attributeIds);
        }

        if (empty($attributeIds)) {
            $attributeIds = $this->_selectAttributes;
        }

        $storeId = $this->getStoreId();
        $entityIdField = $this->getEntity()->getEntityIdField();
        $tmpTableName = $this->_createTempTableWithProductIds(array_keys($this->_itemsById));

        if (!$storeId) {
            /** @var Zend_Db_Select $select */
            $select = $this->_conn->select()
                ->from($table, array($entityIdField, 'attribute_id'))
                ->where('entity_type_id = ?', $this->getEntity()->getTypeId())
                ->where('attribute_id IN (?)', $attributeIds);
            $select->where('store_id = ?', $this->getDefaultStoreId());

            $select->join($this->getTable($tmpTableName), $entityIdField . ' = product_id', '');
        } else {
            $joinCondition = array(
                't_s.attribute_id = t_d.attribute_id',
                't_s.entity_id = t_d.entity_id',
                $this->_conn->quoteInto('t_s.store_id = ?', $storeId),
            );
            $select = $this->_conn->select()
                ->from(array('t_d' => $table), array($entityIdField, 'attribute_id'))
                ->joinLeft(array('t_s' => $table), implode(' AND ', $joinCondition), array())
                ->where('t_d.entity_type_id = ?', $this->getEntity()->getTypeId())
                ->where('t_d.attribute_id IN (?)', $attributeIds)
                ->where('t_d.store_id = ?', 0);

            $select->join($this->getTable($tmpTableName), "t_d.$entityIdField = product_id", '');
        }

        return $select;
    }

    /**
     * @param   array   $productIds
     * @return  string
     */
    protected function _createTempTableWithProductIds(array $productIds)
    {
        Varien_Profiler::start(__METHOD__);

        // Create an unique temporary table name
        $tmpTableName = 'tmp_mirakl_seller_products_' . uniqid();

        // Temporary table definition
        $tmpTable = $this->_conn
            ->newTable($this->getTable($tmpTableName))
            ->addColumn(
                'product_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                    'unsigned' => true, 'nullable' => false, 'default' => '0'
                )
            );

        // Create the temporary table
        $this->_conn->createTemporaryTable($tmpTable);

        // Insert all product ids in the temporary table
        $this->_conn->insertArray($this->getTable($tmpTableName), array('product_id'), $productIds);

        Varien_Profiler::stop(__METHOD__);

        return $tmpTableName;
    }

    /**
     * @return  Mage_Catalog_Model_Product_Media_Config
     */
    public function getMediaConfig()
    {
        return Mage::getSingleton('catalog/product_media_config');
    }

    /**
     * Returns parent ids of specified product ids
     *
     * @param   array   $productIds
     * @return  array
     */
    protected function _getParentProductIds(array $productIds)
    {
        if (empty($productIds)) {
            return array();
        }

        $select = $this->_conn->select()
            ->from($this->getTable('catalog/product_super_link'), array('product_id', 'parent_id'))
            ->where('product_id IN (?)', $productIds);

        $parentIds = array_fill_keys($productIds, array());
        foreach ($this->_conn->fetchAll($select) as $row) {
            $productId = $row['product_id'];
            $parentIds[$productId][] = (int) $row['parent_id'];
        }

        return $parentIds;
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getProductCategoryIds(array $productIds)
    {
        $select = $this->_conn
            ->select()
            ->from($this->_productCategoryTable, array('product_id', 'category_id'))
            ->where('product_id IN (?)', $productIds);

        $categoryIds = array();

        $stmt = $this->_conn->query($select);
        while ($row = $stmt->fetch()) {
            $productId = $row['product_id'];
            if (!isset($categoryIds[$productId])) {
                $categoryIds[$productId] = array();
            }

            if (null !== $row['category_id']) {
                $categoryIds[$productId][] = (int) $row['category_id'];
            }
        }

        unset($stmt);

        return $categoryIds;
    }

    /**
     * @param   array   $productIds
     * @return  array
     */
    public function getProductImages(array $productIds)
    {
        $storeId = $this->getStoreId();
        if (!$storeId) {
            // Use default store view to avoid joining tables twice on store_id = 0
            $storeId = Mage::app()->getDefaultStoreView()->getId();
        }

        $attribute = Mage::getResourceSingleton('catalog/product')->getAttribute('image');
        $attributeId = $attribute ? $attribute->getId() : null;

        $select = $this->_conn->select()
            ->from(
                array('mg' => $this->getTable('catalog/product_attribute_media_gallery')),
                array('entity_id', 'file' => 'value')
            )
            ->joinLeft(
                array('mgv' => $this->getTable('catalog/product_attribute_media_gallery_value')),
                '(mg.value_id = mgv.value_id AND mgv.store_id = ' . $storeId . ')',
                array('label', 'position')
            )
            ->joinLeft(
                array('mgvbi' => $this->getTable('catalog_product_entity_varchar')),
                '(mg.entity_id = mgvbi.entity_id AND mg.value = mgvbi.value AND ' .
                    'mgvbi.store_id = ' . $storeId . ' AND mgvbi.attribute_id = ' . $attributeId. ')',
                array('base_image' => 'value_id')
            )
            ->joinLeft(
                array('mgdv' => $this->getTable('catalog/product_attribute_media_gallery_value')),
                '(mg.value_id = mgdv.value_id AND mgdv.store_id = 0)',
                array('label_default' => 'label', 'position_default' => 'position')
            )
            ->joinLeft(
                array('mgdvbi' => $this->getTable('catalog_product_entity_varchar')),
                '(mg.entity_id = mgdvbi.entity_id AND mg.value = mgdvbi.value AND ' .
                    'mgdvbi.store_id = 0 AND mgdvbi.attribute_id = ' . $attributeId. ')',
                array('base_image_default' => 'value_id')
            )
            ->where('mg.entity_id IN (?)', $productIds)
            ->order(array('base_image DESC', 'base_image_default DESC', 'position ASC', 'position_default ASC', 'file ASC'));

        $images = array();
        $stmt = $this->_conn->query($select);
        while ($row = $stmt->fetch()) {
            $productId = $row['entity_id'];
            if (!isset($images[$productId])) {
                $images[$productId] = array();
            }

            $images[$productId][] = $row;
        }

        unset($stmt);

        return $images;
    }

    /**
     * {@inheritdoc}
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(array(self::MAIN_TABLE_ALIAS => $this->getEntity()->getEntityTable()), array('entity_id'));
        if ($typeId = $this->getEntity()->getTypeId()) {
            $this->addAttributeToFilter('entity_type_id', $typeId);
        }

        return $this;
    }

    /**
     * Checks if specified attribute is using options or not
     *
     * @param   EavAttribute    $attribute
     * @return  bool
     */
    public function isAttributeUsingOptions(EavAttribute $attribute)
    {
        $model = Mage::getModel($attribute->getSourceModel());
        $backend = $attribute->getBackendType();

        return $attribute->usesSource() &&
            ($backend == 'int' && $model instanceof Mage_Eav_Model_Entity_Attribute_Source_Table) ||
            ($backend == 'varchar' && $attribute->getFrontendInput() == 'multiselect');
    }

    /**
     * {@inheritdoc}
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        $this->_renderFilters();
        $this->_renderOrders();

        $this->_loadEntities($printQuery, $logQuery);
        $this->_loadAttributes($printQuery, $logQuery);

        $this->_setIsLoaded();

        return $this;
    }

    /**
     * @param   Listing $listing
     * @param   array   $productAttribute
     * @param   array   $eavAttribute
     * @param   bool    $price
     * @param   bool    $qtyIncrements
     * @return  $this
     */
    public function overrideByParentData(
        $listing,
        $productAttribute = array(),
        $eavAttribute = array(),
        $price = false,
        $qtyIncrements = false
    ) {
        if ($this->getFlag('parent_data_overriden') || empty($this->_items)) {
            return $this;
        }

        $productIds = array_keys($this->_items);

        /** @var MiraklSeller_Core_Helper_Config $config */
        $config = Mage::helper('mirakl_seller/config');

        /** @var MiraklSeller_Core_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('mirakl_seller/product_collection');

        if (count($productAttribute)) {
            $collection->addFieldToSelect($productAttribute);
        }

        foreach ($eavAttribute as $attrCode) {
            $collection->addAttribute($attrCode);
        }

        if ($price) {
            $collection->addTierPricesToSelect($listing->getWebsiteId(), $config->getCustomerGroup())
                ->addListingPriceData($listing)
                ->addAttributeToSelect(array('special_price', 'special_from_date', 'special_to_date'));
        }

        if ($qtyIncrements) {
            $collection->joinTable(
                'cataloginventory/stock_item',
                'product_id = entity_id',
                array('use_config_enable_qty_inc', 'enable_qty_increments',
                    'use_config_qty_increments', 'qty_increments'),
                '{{table}}.stock_id = 1',
                'left'
            );
        }

        $this->_linkToChildren($productIds, $collection->getSelect());

        foreach ($collection as $data) {
            $parentId = $data['entity_id'];
            unset($data['entity_id']);
            $data['parent_id'] = $parentId;

            $entityIds = explode(',', $data['entity_ids']);
            unset($data['entity_ids']);

            foreach ($entityIds as $entityId) {
                // If product have multiple parent, keep data from the first
                if (!isset($this->_items[$entityId]['parent_id'])) {
                    $this->_items[$entityId] = array_merge($this->_items[$entityId], $data);
                }
            }
        }

        $this->setFlag('parent_data_overriden', true);

        return $this;
    }

    /**
     * @param   array               $childrenIds
     * @param   Varien_Db_Select    $select
     * @return  $this
     */
    protected function _linkToChildren($childrenIds, $select = null)
    {
        if (!$select) {
            $select = $this->getSelect();
        }

        $storeId = $this->getStoreId();

        $visibilityAttribute = Mage::getResourceSingleton('catalog/product')->getAttribute('visibility');
        $visibilityId = $visibilityAttribute ? $visibilityAttribute->getId() : null;
        $visibilityValues = implode(',', Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());

        $childIdsSql = new \Zend_Db_Expr("GROUP_CONCAT(DISTINCT link.product_id SEPARATOR ',')");
        $select->joinLeft(
                array('link' => $this->getTable('catalog/product_super_link')),
                'link.parent_id = e.entity_id',
                array('entity_ids' => $childIdsSql)
            )
            ->joinLeft(
                array('visibiliy_store' => $this->getTable('catalog_product_entity_int')),
                'visibiliy_store.entity_id = link.product_id AND ' .
                    "visibiliy_store.attribute_id = $visibilityId AND visibiliy_store.store_id = $storeId",
                array()
            )
            ->joinLeft(
                array('default_visibiliy_store' => $this->getTable('catalog_product_entity_int')),
                'default_visibiliy_store.entity_id = link.product_id AND ' .
                "default_visibiliy_store.attribute_id = $visibilityId AND default_visibiliy_store.store_id = 0",
                array()
            )
            ->where('link.product_id IN (?)', $childrenIds)
            ->where(
                "visibiliy_store.value NOT IN ($visibilityValues) OR " .
                "(visibiliy_store.value IS NULL AND default_visibiliy_store.value NOT IN ($visibilityValues))"
            )
            ->group('e.entity_id');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function _loadEntities($printQuery = false, $logQuery = false)
    {
        if ($this->_pageSize) {
            $this->getSelect()->limitPage($this->getCurPage(), $this->_pageSize);
        }

        $this->printLogQuery($printQuery, $logQuery);

        try {
            $query = $this->_prepareSelect($this->getSelect());
            $rows = $this->_fetchAll($query);
        } catch (Exception $e) {
            Mage::printException($e, $query);
            $this->printLogQuery(true, true, $query);
            throw $e;
        }

        $entityIdField = $this->getEntity()->getEntityIdField();
        foreach ($rows as $row) {
            $entityId = $row[$entityIdField];
            $this->_items[$entityId] = $row;
            if (isset($this->_itemsById[$entityId])) {
                $this->_itemsById[$entityId][] = $row;
            } else {
                $this->_itemsById[$entityId] = array($row);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _setItemAttributeValue($valueInfo)
    {
        $entityIdField = $this->getEntity()->getEntityIdField();
        $entityId      = $valueInfo[$entityIdField];
        if (!isset($this->_itemsById[$entityId])) {
            throw Mage::exception(
                'Mage_Eav',
                Mage::helper('eav')->__('Data integrity: No header row found for attribute')
            );
        }

        $attributeCode = array_search($valueInfo['attribute_id'], $this->_selectAttributes);
        if (!$attributeCode) {
            $attribute = Mage::getSingleton('eav/config')->getCollectionAttribute(
                $this->getEntity()->getType(),
                $valueInfo['attribute_id']
            );
            $attributeCode = $attribute->getAttributeCode();
        }

        foreach ($this->_itemsById[$entityId] as &$data) {
            $data[$attributeCode] = $valueInfo['value'];
            $this->_items[$entityId][$attributeCode] = $valueInfo['value'];
        }

        unset($data);

        return $this;
    }
}
