<?php

class MiraklSeller_Core_Model_System_Config_Source_Attribute_Dropdown
{
    /**
     * Retrieves all product attributes collection
     *
     * @return  Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getAttributeCollection()
    {
        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->setOrder('frontend_label', 'ASC');

        return $collection;
    }

    /**
     * Retrieves all product attributes in order to choose a potential mapping for optional fields in OF01 in configuration
     *
     * @return  array
     */
    public function toOptionArray()
    {
        $options = array(
            array(
                'value' => '',
                'label' => Mage::helper('adminhtml')->__('-- Please Select --'),
            )
        );

        $collection = $this->getAttributeCollection();
        foreach ($collection as $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            if ($attribute->getFrontendLabel()) {
                $options[] = array(
                    'value' => $attribute->getAttributeCode(),
                    'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
                );
            }
        }

        return $options;
    }
}
