<?php

class MiraklSeller_Core_Model_Resource_Product
{
    /**
     * @var Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    protected $_allowedAttributes;

    /**
     * Default excluded attribute codes
     *
     * @var array
     */
    protected $_excludedAttributesRegexpArray = array(
        'custom_layout.*',
        'options_container',
        'custom_design.*',
        'page_layout',
        'tax_class_id',
        'is_recurring',
        'recurring_profile',
        'tier_price',
        'group_price',
        'price.*',
        'status',
        'visibility',
        'url_key',
        'special_price',
        'special_from_date',
        'special_to_date',
    );

    /**
     * Excluded attribute types
     *
     * @var array
     */
    protected $_excludedTypes = array('gallery', 'hidden', 'multiline', 'media_image');

    /**
     * Retrieves exportable product attributes
     *
     * @return  Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getExportableAttributes()
    {
        if (null === $this->_allowedAttributes) {
            $collection = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->setOrder('frontend_label', 'ASC');

            foreach ($collection as $key => $attribute) {
                /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
                if (!$this->_isAttributeExportable($attribute)) {
                    $collection->removeItemByKey($key);
                }
            }

            Mage::dispatchEvent('mirakl_seller_exportable_product_attributes', array('attributes' => $collection));

            $this->_allowedAttributes = $collection;
        }

        return $this->_allowedAttributes;
    }

    /**
     * Retrieves exportable product attribute codes
     *
     * @return  array
     */
    public function getExportableAttributeCodes()
    {
        return $this->getExportableAttributes()->walk('getAttributeCode');
    }

    /**
     * @param   Mage_Catalog_Model_Resource_Eav_Attribute   $attribute
     * @return  bool
     */
    protected function _isAttributeExportable(Mage_Catalog_Model_Resource_Eav_Attribute $attribute)
    {
        $exclAttrRegexp = sprintf('/^(%s)$/i', implode('|', $this->_excludedAttributesRegexpArray));

        return $attribute->getFrontendLabel()
            && !$attribute->isStatic()
            && !in_array($attribute->getData('frontend_input'), $this->_excludedTypes)
            && !preg_match($exclAttrRegexp, $attribute->getAttributeCode());
    }

    /**
     * Builds exportable attributes options
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = array();

        foreach ($this->getExportableAttributes() as $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            $options[] = array(
                'value' => $attribute->getAttributeId(),
                'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
            );
        }

        return $options;
    }
}
