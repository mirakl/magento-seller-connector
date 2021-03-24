<?php

class MiraklSeller_Core_Model_System_Config_Source_Attribute_DropdownText
    extends MiraklSeller_Core_Model_System_Config_Source_Attribute_Dropdown
{
    /**
     * Retrieves all product attributes collection
     * Filtered by FrontendInputType Text
     *
     * @return  Mage_Catalog_Model_Resource_Product_Attribute_Collection
     */
    public function getAttributeCollection()
    {
        $collection = parent::getAttributeCollection();
        $collection->setFrontendInputTypeFilter('text');

        return $collection;
    }
}
