<?php

use MiraklSeller_Api_Model_Connection as Connection;
use MiraklSeller_Core_Model_Listing as Listing;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Core_Model_Cron_Listing
{
    /**
     * @var MiraklSeller_Core_Helper_Connection
     */
    protected $_connectionHelper;

    /**
     * @var MiraklSeller_Core_Helper_Listing
     */
    protected $_listingHelper;

    /**
     * @var bool
     */
    protected $_offerFull = true;

    /**
     * Init
     */
    public function __construct()
    {
        $this->_connectionHelper = Mage::helper('mirakl_seller/connection');
        $this->_listingHelper    = Mage::helper('mirakl_seller/listing');
    }

    /**
     * Refresh all listings of all connections
     */
    public function refreshAll()
    {
        /** @var Connection $connection */
        foreach ($this->_getAllConnections() as $connection) {
            $listings = $this->_connectionHelper->getActiveListings($connection);

            /** @var Listing $listing */
            foreach ($listings as $listing) {
                $process = $this->_listingHelper->refresh($listing, Process::TYPE_CRON);
                $process->run();
            }
        }
    }

    /**
     * Export offers for all listings of all connections
     */
    public function exportOffers()
    {
        $this->_exportAllConnections(Listing::TYPE_OFFER);
    }

    /**
     * Export offers for all listings of all connections
     */
    public function exportOffersDelta()
    {
        $this->_offerFull = false;
        $this->exportOffers();
    }

    /**
     * Export products for all listings of all connections
     */
    public function exportProducts()
    {
        $this->_exportAllConnections(Listing::TYPE_PRODUCT);
    }

    /**
     * Export specified type for all listings of all connections
     *
     * @param   string  $exportType
     */
    protected function _exportAllConnections($exportType)
    {
        /** @var Connection $connection */
        foreach ($this->_getAllConnections() as $connection) {
            $this->_exportConnection($connection, $exportType);
        }
    }

    /**
     * Export specified type for all listings of specified connection
     *
     * @param   Connection  $connection
     * @param   string      $exportType
     */
    protected function _exportConnection(Connection $connection, $exportType)
    {
        $listings = $this->_connectionHelper->getActiveListings($connection);

        /** @var Listing $listing */
        foreach ($listings as $listing) {
            $this->_exportListing($listing, $exportType);
        }
    }

    /**
     * Export specified type of specified listing
     *
     * @param   Listing $listing
     * @param   string  $exportType
     */
    protected function _exportListing($listing, $exportType)
    {
        $processes = $this->_listingHelper->export(
            $listing,
            $exportType,
            $this->_offerFull,
            Listing::PRODUCT_MODE_PENDING,
            Process::TYPE_CRON
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