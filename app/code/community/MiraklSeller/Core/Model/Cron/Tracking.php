<?php

use MiraklSeller_Api_Model_Connection as Connection;
use MiraklSeller_Core_Model_Listing as Listing;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Model_Cron_Tracking
{
    /**
     * @var MiraklSeller_Core_Helper_Connection
     */
    protected $_connectionHelper;

    /**
     * @var MiraklSeller_Core_Helper_Tracking
     */
    protected $_trackingHelper;

    /**
     * Init
     */
    public function __construct()
    {
        $this->_connectionHelper = Mage::helper('mirakl_seller/connection');
        $this->_trackingHelper   = Mage::helper('mirakl_seller/tracking');
    }

    /**
     * Update all trackings
     */
    public function updateAll()
    {
        $this->_updateAllConnections(Listing::TYPE_ALL);
    }

    /**
     * Update specified type for all listings of all connections
     *
     * @param   string  $exportType
     */
    protected function _updateAllConnections($exportType)
    {
        /** @var Connection $connection */
        foreach ($this->_getAllConnections() as $connection) {
            $this->_updateConnection($connection, $exportType);
        }
    }

    /**
     * Update specified type for all listings of specified connection
     *
     * @param   Connection  $connection
     * @param   string      $exportType
     */
    protected function _updateConnection(Connection $connection, $exportType)
    {
        $listings = $this->_connectionHelper->getActiveListings($connection);

        /** @var Listing $listing */
        foreach ($listings as $listing) {
            $this->_updateListing($listing, $exportType);
        }
    }

    /**
     * Update specified type of specified listing
     *
     * @param   Listing $listing
     * @param   string  $exportType
     */
    protected function _updateListing($listing, $exportType)
    {
        $processes = $this->_trackingHelper->updateListingTrackingsByType(
            $listing->getId(), $exportType, Process::TYPE_CRON
        );

        /** @var Process $process */
        foreach ($processes as $process) {
            $process->run();
        }
    }

    /**
     * Returns all active connections
     *
     * @return  MiraklSeller_Api_Model_Resource_Connection_Collection
     */
    protected function _getAllConnections()
    {
        return Mage::getModel('mirakl_seller_api/connection')->getCollection();
    }
}