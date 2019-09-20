<?php

class MiraklSeller_Core_Model_System_Config_Source_Customer_DropdownCustomerGroup
{
    /**
     * Retrieves all customer groups collection
     *
     * @return  Mage_Customer_Model_Resource_Group_Collection
     */
    public function getCustomerGroupCollection()
    {
        return Mage::getResourceModel('customer/group_collection');
    }

    /**
     * Retrieves all customer groups
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->getCustomerGroupCollection()->toOptionArray();
    }
}
