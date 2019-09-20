<?php
/**
 * @method  $this                                   addFieldToFilter($field, $condition = null)
 * @method  MiraklSeller_Core_Model_Offer           getFirstItem()
 * @method  MiraklSeller_Core_Model_Resource_Offer  getResource()
 */
class MiraklSeller_Core_Model_Resource_Offer_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
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
        $this->_init('mirakl_seller/offer');
    }
}
