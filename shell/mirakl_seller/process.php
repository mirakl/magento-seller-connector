<?php
require dirname(__DIR__) . '/abstract.php';

class MiraklSeller_Shell_Process extends Mage_Shell_Abstract
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
     * Run script
     */
    public function run()
    {
        try {
            /** @var MiraklSeller_Process_Model_Process $process */
            if ($id = $this->getArg('run')) {
                $process = Mage::getModel('mirakl_seller_process/process')->load($id);
                if (!$process->getId()) {
                    $this->_fault('This process no longer exists.');
                }
                if (!$process->isPending() && !$this->getArg('force')) {
                    $this->_fault('This process has already been executed. Use --force option to force execution.');
                }
                if (!$this->_quiet) {
                    $process->addOutput('cli');
                }
                $process->run(true);
            } elseif ($this->getArg('pending')) {
                $process = Mage::helper('mirakl_seller_process')->getPendingProcess();
                if ($process) {
                    $this->_echo(sprintf('Processing #%d', $process->getId()));
                    if (!$this->_quiet) {
                        $process->addOutput('cli');
                    }
                    $process->run();
                } else {
                    $this->_echo('Nothing to process');
                }
            } elseif ($this->getArg('stop-errors')) {
                $this->_echo('Stopping running processes that have an error report');
                $stopped = Mage::helper('mirakl_seller_process/error')->stopProcessesInError();
                $this->_echo("Stopped $stopped process(es)");
            } elseif ($this->getArg('timeout')) {
                $this->_echo('Marking running processes as timeout if execution time has exceeded the configured delay in admin');
                $delay = Mage::helper('mirakl_seller_process/config')->getTimeoutDelay();
                $this->_echo("Delay: $delay min");
                $updated = Mage::getResourceModel('mirakl_seller_process/process')->markAsTimeout($delay);
                $this->_echo("Updated $updated process(es)");
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
Usage:  php -f {$_SERVER['SCRIPT_NAME']} -- [options]

  --pending         Process older pending process (one by one)
  --run <id>        Run specified process
  --force           Force process running even if it's not in pending status
  --stop-errors     Stops running processes that have an error report
  --timeout         Marks processes as timeout if execution time has exceeded the configured delay in admin
  --quiet           Shutdown standard output messages
  help              This help

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

        if (!Mage::helper('core')->isModuleEnabled('MiraklSeller_Process')) {
            $this->_fault('Please enable MiraklSeller_Process module before running this script.');
        }

        return true;
    }
}

$shell = new MiraklSeller_Shell_Process();
$shell->run();
