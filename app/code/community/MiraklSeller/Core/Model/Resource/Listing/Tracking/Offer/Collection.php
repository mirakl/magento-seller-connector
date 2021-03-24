<?php
/**
 * @method  $this                                                   addFieldToFilter($field, $condition = null)
 * @method  MiraklSeller_Core_Model_Listing_Tracking_Offer          getFirstItem()
 * @method  MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer getResource()
 */
class MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer_Collection
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
        $this->_init('mirakl_seller/listing_tracking_offer');
    }

    /**
     * @return  $this
     */
    public function addExcludeOfferStatusCompleteFilter()
    {
        return $this->addFieldToFilter(
            'import_status', array(
                array('nin' => MiraklSeller_Core_Model_Listing_Tracking_Status_Offer::getCompleteStatuses()),
                array('null' => true),
            )
        );
    }
}
