<?php
require dirname(__DIR__) . '/../abstract.php';

use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Shell_Order_Accept extends Mage_Shell_Abstract
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
     * Creates a process that will accept all the Mirakl orders of the specified connection
     *
     * @param   int $connectionId
     * @return  Process
     */
    protected function _createProcess($connectionId)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType(Process::TYPE_CLI)
            ->setName('Accept Mirakl orders')
            ->setHelper('mirakl_seller_sales/order_process')
            ->setMethod('acceptConnectionOrders')
            ->setParams([$connectionId])
            ->save();

        return $process;
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
     * @param   int $connectionId
     * @return  Process
     */
    protected function _acceptOrdersFromConnection($connectionId)
    {
        if (!Mage::helper('mirakl_seller_sales/config')->isAutoAcceptOrdersEnabled()) {
            $this->_fault('Auto acceptance of Mirakl orders is disabled in Magento configuration.');
        }

        $process = $this->_createProcess($connectionId);
        if (!$this->_quiet) {
            $process->addOutput('cli');
        }

        return $process->run();
    }

    /**
     * @return  $this
     */
    protected function _acceptOrdersFromAllConnections()
    {
        $this->_echo('All Mirakl orders will be accepted from all connections');

        $connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();

        foreach ($connections as $connection) {
            $this->_acceptOrdersFromConnection($connection->getId());
        }

        return $this;
    }

    /**
     * Run script
     */
    public function run()
    {
        try {
            if ($this->getArg('all')) {
                $this->_acceptOrdersFromAllConnections();
            } elseif ($connectionId = $this->getArg('connection')) {
                $this->_acceptOrdersFromConnection($connectionId);
            } else {
                $this->_echo($this->usageHelp());
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
Usage: php -f {$_SERVER['SCRIPT_NAME']} -- [--connection <connection_id>] [--all] [options]

  --connection <connection_id>  Identifier of the connection to accept Mirakl orders from
  --all                         Accept all Mirakl orders of all connections
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

$shell = new MiraklSeller_Shell_Order_Accept();
$shell->run();
