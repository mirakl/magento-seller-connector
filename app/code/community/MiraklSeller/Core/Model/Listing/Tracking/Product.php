<?php
/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  int     getListingId()
 * @method  $this   setListingId(int $listingId)
 * @method  int     getImportId()
 * @method  $this   setImportId(int $productImportId)
 * @method  string  getImportStatus()
 * @method  $this   setImportStatus(string $productImportStatus)
 * @method  string  getImportStatusReason()
 * @method  $this   setImportStatusReason(string $productImportStatusReason)
 * @method  string  getIntegrationErrorReport()
 * @method  $this   setIntegrationErrorReport(string $errorReport)
 * @method  string  getTransformationErrorReport()
 * @method  $this   setTransformationErrorReport(string $errorReport)
 * @method  string  getIntegrationSuccessReport()
 * @method  $this   setIntegrationSuccessReport(string $integrationReport)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 *
 * @method  MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection    getCollection()
 * @method  MiraklSeller_Core_Model_Resource_Listing_Tracking_Product               getResource()
 */
class MiraklSeller_Core_Model_Listing_Tracking_Product extends Mage_Core_Model_Abstract
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
        $this->_init('mirakl_seller/listing_tracking_product');
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
