<?php
/**
 * @method  $this                                                       addFieldToFilter($field, $condition = null)
 * @method  MiraklSeller_Core_Model_Listing_Tracking_Product            getFirstItem()
 * @method  MiraklSeller_Core_Model_Resource_Listing_Tracking_Product   getResource()
 */
class MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection
    extends MiraklSeller_Core_Model_Resource_Listing_Tracking_Collection_Abstract
{
    /**
     * Custom id field
     *
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize model
     */
    public function _construct()
    {
        $this->_init('mirakl_seller/listing_tracking_product');
    }

    /**
     * @return  $this
     */
    public function addExcludeProductStatusFinalFilter()
    {
        return $this->addFieldToFilter(
            'import_status', array(
                array('nin' => MiraklSeller_Core_Model_Listing_Tracking_Status_Product::getFinalStatuses()),
                array('null' => true),
            )
        );
    }

    /**
     * Get last product tracking from listing sorted by creation date and not in a final status
     *
     * @param   int     $listingId
     * @return  $this
     */
    public function getLastProductTrackingForListing($listingId)
    {
        $this->addExcludeProductStatusFinalFilter();
        $this->getSelect()->order('created_at ' . Zend_Db_Select::SQL_DESC);
        $this->getSelect()->limit(1);

        return $this->addListingFilter($listingId);
    }
}
