<?php
require dirname(__DIR__) . '/../abstract.php';

use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Shell_Order_Import extends Mage_Shell_Abstract
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
     * Creates a process that will synchronize all the Mirakl orders of the specified connection
     *
     * @param   int $connectionId
     * @return  Process
     */
    protected function _createProcess($connectionId)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType(Process::TYPE_CLI)
            ->setName('Synchronize Mirakl orders')
            ->setHelper('mirakl_seller_sales/order_process')
            ->setMethod('synchronizeConnection')
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
    protected function _importOrdersFromConnection($connectionId)
    {
        $process = $this->_createProcess($connectionId);
        if (!$this->_quiet) {
            $process->addOutput('cli');
        }

        return $process->run();
    }

    /**
     * @return  $this
     */
    protected function _importOrdersFromAllConnections()
    {
        $this->_echo('All Mirakl orders will be imported from all connections');

        $connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();

        foreach ($connections as $connection) {
            $this->_importOrdersFromConnection($connection->getId());
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
                $this->_importOrdersFromAllConnections();
            } elseif ($connectionId = $this->getArg('connection')) {
                $this->_importOrdersFromConnection($connectionId);
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

  --connection <connection_id>  Identifier of the connection to import Mirakl orders from
  --all                         Import all Mirakl orders of all connections
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

$shell = new MiraklSeller_Shell_Order_Import();
$shell->run();
