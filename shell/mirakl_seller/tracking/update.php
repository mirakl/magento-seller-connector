<?php
require dirname(__DIR__) . '/../abstract.php';

use MiraklSeller_Api_Model_Connection as Connection;
use MiraklSeller_Core_Model_Listing as Listing;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Shell_Tracking_Update extends Mage_Shell_Abstract
{
    /**
     * @var bool
     */
    protected $_quiet = false;

    /**
     * @return  $this
     */
    protected function _construct()
    {
        $this->_quiet = (bool) $this->getArg('quiet');

        Mage::getModel('mirakl_seller/autoload')->registerAutoload();

        return $this;
    }

    /**
     * @param   string  $str
     */
    protected function _echo($str)
    {
        if (!$this->_quiet) {
            printf('%s%s', $str, PHP_EOL);
        }
    }

    /**
     * @param   string  $str
     */
    protected function _fault($str)
    {
        throw new \Exception($str);
    }

    /**
     * @param   int $listingId
     * @return  $this
     */
    protected function _updateListingTrackings($listingId)
    {
        $processes = Mage::helper('mirakl_seller/tracking')->updateListingTrackingsByType(
            $listingId, $this->_getType(), Process::TYPE_CLI
        );

        /** @var Process $process */
        foreach ($processes as $process) {
            if (!$this->_quiet) {
                $process->addOutput('cli');
            }
            $process->run();
        }

        return $this;
    }

    /**
     * @param   int|Connection  $connection
     * @return  $this
     */
    protected function _updateConnectionListingTrackings($connection)
    {
        if (!$connection instanceof Connection) {
            $connection = Mage::getModel('mirakl_seller_api/connection')->load($connection);
        }

        $this->_echo(sprintf('Connection %s (%s) will be treated', $connection->getName(), $connection->getId()));

        $listings = Mage::helper('mirakl_seller/connection')->getActiveListings($connection);

        if ($listings->count() > 0) {
            foreach ($listings as $listing) {
                $this->_echo(sprintf(' --> Active listing %s (%s) will be treated', $listing->getName(), $listing->getId()));
                $this->_updateListingTrackings($listing->getId());
            }
        } else {
            $this->_echo('No active listing associated with this connection');
        }

        return $this;
    }

    /**
     * @return  $this
     */
    protected function _updateAllListingTrackings()
    {
        $this->_echo('All active listings will be treated for all connections');

        $activeConnections =  Mage::getResourceModel('mirakl_seller_api/connection_collection')
            ->setOrder('name', 'ASC');

        foreach ($activeConnections as $activeConnection) {
            $this->_updateConnectionListingTrackings($activeConnection);
        }

        return $this;
    }

    /**
     * @return  string
     */
    protected function _getType()
    {
        $type = $this->getArg('type');

        if (empty($type)) {
            $type = Listing::TYPE_ALL;
        }

        $allowedTypes = Listing::getAllowedTypes();

        if (!in_array($type, $allowedTypes)) {
            $this->_fault('Available types are: ' . implode(', ', $allowedTypes));
        }

        return $type;
    }

    /**
     * Run script
     */
    public function run()
    {
        try {
            if ($this->getArg('all')) {
                $this->_updateAllListingTrackings();
            } else {
                if ($listing = $this->getArg('listing')) {
                    $this->_updateListingTrackings($listing);
                }
                if ($connection = $this->getArg('connection')) {
                    $this->_updateConnectionListingTrackings($connection);
                }
            }
        } catch (\Exception $e) {
            $this->_echo('ERROR: ' . $e->getMessage());
        }
    }

    /**
     * @return  string
     */
    public function usageHelp()
    {
        return <<<USAGE
This script will update all tracking's of the specified listing.

Usage: php -f {$_SERVER['SCRIPT_NAME']} -- --type <type> --listing <listing_id> --connection <connection_id> [--all] [options]

  --type <type>                 Determine which export is made. Available values: ALL (default), PRODUCT, OFFER.
  --listing <listing_id>        Identifier of the listing
  --connection <connection_id>  Identifier of the connection
  --all                         Export all active listings
  --quiet                       Shutdown standard output messages
  --help                        This help

USAGE;
    }

    /**
     * @return  bool
     */
    protected function _validate()
    {
        if (!Mage::isInstalled()) {
            $this->_fault('Please install Magento before running this script.');
        }

        if (!Mage::helper('core')->isModuleEnabled('MiraklSeller_Core')) {
            $this->_fault('Please enable MiraklSeller_Core module before running this script.');
        }

        return true;
    }
}

$shell = new MiraklSeller_Shell_Tracking_Update();
$shell->run();
