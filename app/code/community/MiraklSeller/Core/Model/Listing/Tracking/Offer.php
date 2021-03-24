<?php
/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  int     getListingId()
 * @method  $this   setListingId(int $listingId)
 * @method  int     getImportId()
 * @method  $this   setImportId(int $offerImportId)
 * @method  string  getImportStatus()
 * @method  $this   setImportStatus(string $offerImportStatus)
 * @method  string  getErrorReport()
 * @method  $this   setErrorReport(string $errorReport)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 *
 * @method  MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer_Collection  getCollection()
 * @method  MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer             getResource()
 */
class MiraklSeller_Core_Model_Listing_Tracking_Offer extends Mage_Core_Model_Abstract
{
    /**
     * @var MiraklSeller_Core_Model_Listing
     */
    protected $_listing;

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller/listing_tracking_offer');
    }

    /**
     * @return  MiraklSeller_Core_Model_Listing
     */
    public function getListing()
    {
        if (null === $this->_listing) {
            $this->_listing = Mage::getModel('mirakl_seller/listing')->load($this->getListingId());
        }

        return $this->_listing;
    }
}
