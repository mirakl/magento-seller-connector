<?php

use MiraklSeller_Process_Model_Process as Process;

/**
 * This class is used to log some potential fatal errors occurring when executing processes.
 * Fatal errors cannot be handled easily to mark processes as STOPPED but we are able to retrieve the error and log it.
 */
class MiraklSeller_Process_Helper_Error extends Mage_Core_Helper_Abstract
{
    const ERROR_FILE_PREFIX = 'process_error_';

    /**
     * Removes potential JSON error file associated with the specified process
     *
     * @param   Process $process
     * @return  bool
     */
    public function deleteProcessError(Process $process)
    {
        if (($file = $this->getProcessErrorFile($process)) && file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Returns the processes error path target
     *
     * @return  string|false
     */
    public function getErrorPath()
    {
        $path = Mage::getConfig()->getOptions()->getVarDir() . DS . 'mirakl' . DS . 'process';

        if (!Mage::getConfig()->createDirIfNotExists($path)) {
            return false;
        }

        return $path;
    }

    /**
     * Returns the file path used for logging any error that would occurs when executing the specified process
     *
     * @param   Process $process
     * @return  string|false
     */
    public function getProcessErrorFile(Process $process)
    {
        if ($path = $this->getErrorPath()) {
            return $path . DS . self::ERROR_FILE_PREFIX . $process->getId() . '.json';
        }

        return false;
    }

    /**
     * Returns error report associated with the specified process if any
     *
     * @param   Process $process
     * @return  array|false
     */
    public function getProcessErrorReport(Process $process)
    {
        if (($file = $this->getProcessErrorFile($process)) && file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return false;
    }

    /**
     * Logs the specified error that occurs when executing the specified process
     *
     * @param   Process $process
     * @param   array   $error
     * @return  bool|int
     */
    public function logProcessError(Process $process, array $error)
    {
        if ($file = $this->getProcessErrorFile($process)) {
            return @file_put_contents($file, json_encode($error, JSON_PRETTY_PRINT));
        }

        return false;
    }

    /**
     * Stops processes that are still running and that have an error file.
     * Returns the number of process stopped.
     *
     * @return  int
     */
    public function stopProcessesInError()
    {
        if ($path = $this->getErrorPath()) {
            $prefix = self::ERROR_FILE_PREFIX;
            $processIds = array();
            foreach (glob("$path/$prefix*.json", GLOB_NOSORT) as $file) {
                preg_match("/$prefix(\d+)\.json/", basename($file), $matches);
                if (isset($matches[1])) {
                    $processIds[] = $matches[1];
                }
            }

            if (!empty($processIds)) {
                /** @var MiraklSeller_Process_Model_Resource_Process_Collection $collection */
                $collection = Mage::getModel('mirakl_seller_process/process')->getCollection();
                $collection->addIdFilter($processIds);

                $stopCount = 0;
                foreach ($collection as $process) {
                    if ($error = $this->getProcessErrorReport($process)) {
                        $process->addOutput('db');
                        $process->output($error['message']);
                        if ($process->canStop()) {
                            $stopCount++;
                            $process->stop(Process::STATUS_STOPPED);
                        }

                        $this->deleteProcessError($process);
                    }
                }

                return $stopCount;
            }
        }

        return 0;
    }
}
