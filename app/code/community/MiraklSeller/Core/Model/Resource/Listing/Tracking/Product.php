<?php

class MiraklSeller_Core_Model_Resource_Listing_Tracking_Product extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize model and primary key field
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller/listing_tracking_product', 'id');
    }

    /**
     * @param   Mage_Core_Model_Abstract    $object
     * @return  array
     */
    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        /** @var MiraklSeller_Core_Model_Listing_Tracking_Product $object */
        $currentTime = Varien_Date::now();
        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($currentTime);
        }

        $object->setUpdatedAt($currentTime);
        $data = parent::_prepareDataForSave($object);

        return $data;
    }

    /**
     * Deletes specified trackings from database
     *
     * @param   array   $ids
     * @return  bool|int
     */
    public function deleteIds(array $ids)
    {
        if (!empty($ids)) {
            return $this->_getWriteAdapter()->delete($this->getMainTable(), array('id IN (?)' => $ids));
        }

        return false;
    }
}
