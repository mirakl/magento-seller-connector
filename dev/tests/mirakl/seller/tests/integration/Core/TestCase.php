<?php
namespace Mirakl\Test\Integration\Core;

use AspectMock\Test;
use Mirakl\MCI\Common\Domain\Product\ProductImportWithTransformationStatus;

abstract class TestCase extends \Mirakl\Test\Integration\TestCase
{
    /**
     * @var array
     */
    protected $_connectionIds = [];

    /**
     * @var array
     */
    protected $_listingIds = [];

    /**
     * @var array
     */
    protected $_productTrackingIds = [];

    protected function tearDown()
    {
        if (!empty($this->_connectionIds)) {
            // Delete created connections
            \Mage::getModel('mirakl_seller_api/connection')->getCollection()
                ->addIdFilter($this->_connectionIds)
                ->walk('delete');
        }

        if (!empty($this->_listingIds)) {
            // Delete created listings
            \Mage::getModel('mirakl_seller/listing')->getCollection()
                ->addIdFilter($this->_listingIds)
                ->walk('delete');
        }

        if (!empty($this->_productTrackingIds)) {
            // Delete created product trackings
            \Mage::getResourceModel('mirakl_seller/listing_tracking_product')
                ->deleteIds($this->_productTrackingIds);
        }

        Test::clean();
    }

    /**
     * @return  \MiraklSeller_Api_Model_Connection
     */
    protected function _createSampleConnection()
    {
        /** @var \MiraklSeller_Api_Model_Connection $connection */
        $connection = \Mage::getModel('mirakl_seller_api/connection');
        $connection->setName('[TEST] Sample connection for integration tests');
        $connection->save();

        $this->_connectionIds[] = $connection->getId();

        return $connection;
    }

    /**
     * @return  \MiraklSeller_Core_Model_Listing
     */
    protected function _createSampleListing()
    {
        $connection = $this->_createSampleConnection();

        /** @var \MiraklSeller_Core_Model_Listing $listing */
        $listing = \Mage::getModel('mirakl_seller/listing');
        $listing->setName('[TEST] Sample listing for integration tests')
            ->setConnectionId($connection->getId())
            ->setIsActive(1)
            ->save();

        $this->_listingIds[] = $listing->getId();

        return $listing;
    }

    /**
     * @return  \MiraklSeller_Core_Model_Listing_Tracking_Product
     */
    protected function _createSampleProductTracking()
    {
        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Core_Model_Listing_Tracking_Product $tracking */
        $tracking = \Mage::getModel('mirakl_seller/listing_tracking_product');
        $tracking->setListingId($listing->getId())
            ->setImportId(mt_rand(2000, 9999))
            ->setImportStatus(ProductImportWithTransformationStatus::COMPLETE)
            ->save();

        $this->_productTrackingIds[] = $tracking->getId();

        return $tracking;
    }

    /**
     * @param   mixed   $builderMock
     * @return  \AspectMock\Proxy\Verifier
     */
    public function mockListingBuilder($builderMock)
    {
        return Test::double(\MiraklSeller_Core_Model_Listing::class, [
            'getBuilder' => $builderMock
        ]);
    }
}