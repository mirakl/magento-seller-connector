<?php
/**
 * @method  $this   addFieldToFilter($field, $condition = null)
 */
abstract class MiraklSeller_Core_Model_Resource_Listing_Tracking_Collection_Abstract
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * @param   int|array   $trackingIds
     * @return  $this
     */
    public function addIdFilter($trackingIds)
    {
        if (empty($trackingIds)) {
            $trackingIds = array(0);
        }

        if (!is_array($trackingIds)) {
            $trackingIds = array($trackingIds);
        }

        return $this->addFieldToFilter('id', array('in' => $trackingIds));
    }

    /**
     * @param   int $listingId
     * @return  $this
     */
    public function addListingFilter($listingId)
    {
        return $this->addFieldToFilter('listing_id', $listingId);
    }

    /**
     * @return  $this
     */
    public function addWithImportIdFilter()
    {
        return $this->addFieldToFilter('import_id', array('gt' => 0));
    }
}
