<?php
/**
 * @method  string  getCreatedAt()
 * @method  $this   setCreatedAt(string $createdAt)
 * @method  $this   setDuration(int $duration)
 * @method  string  getFile()
 * @method  $this   setFile(string $file)
 * @method  string  getHash()
 * @method  $this   setHash(string $hash)
 * @method  string  getHelper()
 * @method  $this   setHelper(string $helper)
 * @method  string  getMethod()
 * @method  $this   setMethod(string $method)
 * @method  string  getMiraklFile()
 * @method  $this   setMiraklFile(string $file)
 * @method  string  getMiraklStatus()
 * @method  $this   setMiraklStatus(string $status)
 * @method  string  getSuccessReport()
 * @method  $this   setSuccessReport(string $report)
 * @method  int     getSynchroId()
 * @method  $this   setSynchroId(int $synchroId)
 * @method  string  getMiraklType()
 * @method  $this   setMiraklType(string $type)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  string  getOutput()
 * @method  $this   setOutput(string $output)
 * @method  $this   setParams(string|array $params)
 * @method  int     getParentId()
 * @method  $this   setParentId(int $parentId)
 * @method  string  getStatus()
 * @method  $this   setStatus(string $status)
 * @method  string  getType()
 * @method  $this   setType(string $type)
 * @method  string  getUpdatedAt()
 * @method  $this   setUpdatedAt(string $updatedAt)
 *
 * @method  MiraklSeller_Process_Model_Process                      load($id, $field = null)
 * @method  MiraklSeller_Process_Model_Resource_Process_Collection  getCollection()
 * @method  MiraklSeller_Process_Model_Resource_Process             getResource()
 */
class MiraklSeller_Process_Model_Process extends Mage_Core_Model_Abstract
{
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_IDLE       = 'idle';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_STOPPED    = 'stopped';
    const STATUS_TIMEOUT    = 'timeout';
    const STATUS_CANCELLED  = 'cancelled';
    const STATUS_ERROR      = 'error';

    const TYPE_API    = 'API';
    const TYPE_CLI    = 'CLI';
    const TYPE_CRON   = 'CRON';
    const TYPE_ADMIN  = 'ADMIN';
    const TYPE_IMPORT = 'IMPORT';

    /**
     * @var MiraklSeller_Process_Model_Output_Interface[]
     */
    protected $_outputs = array();

    /**
     * @var bool
     */
    protected $_running = false;

    /**
     * @var float
     */
    protected $_startedAt;

    /**
     * @var MiraklSeller_Process_Helper_Data
     */
    protected $_helper;

    /**
     * @var MiraklSeller_Process_Helper_Error
     */
    protected $_errorHelper;

    /**
     * @var string
     */
    protected $_decodeMethod = 'unserialize';

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_process/process');
        $this->_helper = Mage::helper('mirakl_seller_process');
        $this->_errorHelper = Mage::helper('mirakl_seller_process/error');
    }

    /**
     * @param   string  $str
     * @return  mixed
     */
    protected function _decode($str)
    {
        return call_user_func($this->_decodeMethod, $str);
    }

    /**
     * @param   string  $errstr
     * @param   string  $errfile
     * @param   int     $errline
     * @throws  \ErrorException
     */
    protected function _handleError($errstr, $errfile, $errline)
    {
        $message = sprintf('%s in %s on line %d', $errstr, $errfile, $errline);
        throw new \ErrorException($message);
    }

    /**
     * @return  void
     */
    protected function _registerErrorHandler()
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                if ($errno == E_USER_ERROR) {
                    $this->_handleError($errstr, $errfile, $errline);
                }
            }
        );

        register_shutdown_function(
            function () {
                $error = error_get_last();
                if (!empty($error) && $error['type'] == E_ERROR) {
                    $this->_errorHelper->logProcessError($this, $error);
                    $this->_handleError($error['message'], $error['file'], $error['line']);
                }
            }
        );
    }

    /**
     * Saves potential uncatched messages in current process and stop it if necessary
     */
    public function __destruct()
    {
        if ($this->_running) {
            if ($output = ob_get_contents()) {
                $this->output($output);
            }

            $this->fail(); // Process has been started but not stopped, an error occurred
        }
    }

    /**
     * @param   mixed   $output
     * @return  $this
     * @throws  Mage_Core_Exception
     */
    public function addOutput($output)
    {
        if (is_string($output)) {
            $output = Mage::getModel('mirakl_seller_process/output_' . $output, $this);
        }

        if (!$output instanceof MiraklSeller_Process_Model_Output_Interface) {
            Mage::throwException('Invalid output specified.');
        }

        $this->_outputs[$output->getType()] = $output;

        return $this;
    }

    /**
     * Marks current process as cancelled and stops execution
     *
     * @param   string|null $message
     * @return  $this
     */
    public function cancel($message = null)
    {
        if ($message) {
            $this->output($message);
        }

        $this->stop(self::STATUS_CANCELLED);

        $this->getChildrenCollection()->walk('cancel', array('Cancelled because parent has been cancelled'));

        return $this;
    }

    /**
     * Returns true if process can be run
     *
     * @return  bool
     */
    public function canRun()
    {
        $parent = $this->getParent();

        return !$this->isProcessing() && !$this->isStatusIdle() && (!$parent || $parent->isCompleted());
    }

    /**
     * @param   bool    $isMirakl
     * @return  bool
     */
    public function canShowFile($isMirakl = false)
    {
        $fileSize = $this->getFileSize($isMirakl);
        $maxSize = Mage::helper('mirakl_seller_process/config')->getShowFileMaxSize(); // in MB

        return $fileSize <= ($maxSize * 1024 * 1024);
    }

    /**
     * Returns true if process can be set to STOPPED status
     *
     * @return  bool
     */
    public function canStop()
    {
        return $this->isProcessing();
    }

    /**
     * Calls current process helper->method()
     *
     * @throws  \RuntimeException
     * @throws  \InvalidArgumentException
     */
    public function execute()
    {
        $this->start();

        try {
            $this->_running = true;

            $this->_errorHelper->deleteProcessError($this);

            @set_time_limit(0);
            @ini_set('memory_limit', -1);

            ob_start();

            if ($this->isProcessing()) {
                throw new \RuntimeException('Process is already running.');
            }

            $this->setStatus(self::STATUS_PROCESSING);

            $helper = $this->getHelperInstance();
            $method = $this->getMethod();

            if (!method_exists($helper, $method)) {
                throw new \InvalidArgumentException("Invalid helper method specified '$method'");
            }

            $this->output($this->_helper->__('Running %s::%s()', get_class($helper), $method), true);

            $args = array($this);
            if ($this->getParams()) {
                $args = array_merge($args, $this->getParams());
            }

            call_user_func_array(array($helper, $method), $args);

            $this->stop();
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        } finally {
            if ($output = ob_get_clean()) {
                $this->output($output, true);
            }

            $this->_running = false;
        }
    }

    /**
     * Marks current process as failed and stops execution
     *
     * @param   string|null $message
     * @return  $this
     */
    public function fail($message = null)
    {
        if ($message) {
            $this->output($message);
        }

        $this->stop(self::STATUS_ERROR);

        $this->getChildrenCollection()->walk('cancel', array('Cancelled because parent has failed'));

        return $this;
    }

    /**
     * @return  MiraklSeller_Process_Model_Resource_Process_Collection
     */
    public function getChildrenCollection()
    {
        $collection = $this->getCollection()
            ->addParentFilter($this->getId());

        return $collection;
    }

    /**
     * @return  int|\DateInterval
     */
    public function getDuration()
    {
        $duration = $this->_getData('duration');
        if (!$duration) {
            if ($this->isProcessing() || $this->isStatusIdle()) {
                $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
                $duration = $start->diff(new \DateTime());
            } elseif ($this->isEnded()) {
                $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
                $end = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getUpdatedAt());
                $duration = $start->diff($end);
            }
        }

        return $duration;
    }

    /**
     * @return  array|false
     */
    public function getErrorReport()
    {
        return $this->_errorHelper->getProcessErrorReport($this);
    }

    /**
     * Returns file size in bytes
     *
     * @param   bool    $isMirakl
     * @return  bool|int
     */
    public function getFileSize($isMirakl = false)
    {
        $filePath = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (strlen($filePath) && is_file($filePath)) {
            return filesize($filePath);
        }

        return false;
    }

    /**
     * Returns file size formatted
     *
     * @param   string  $separator
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getFileSizeFormatted($separator = ' ', $isMirakl = false)
    {
        if ($fileSize = $this->getFileSize($isMirakl)) {
            return $this->_helper->formatSize($fileSize, $separator);
        }

        return false;
    }

    /**
     * @param   bool    $isMirakl
     * @return  string|false
     */
    public function getFileUrl($isMirakl = false)
    {
        $file = $isMirakl ? $this->getMiraklFile() : $this->getFile();

        if (!$file || !file_exists($file)) {
            return false;
        }

        return $this->_helper->getFileUrl($file);
    }

    /**
     * @return  mixed
     * @throws  \InvalidArgumentException
     */
    public function getHelperInstance()
    {
        $name = $this->getHelper();

        if (class_exists($name)) {
            return new $name;
        }

        if (!class_exists(Mage::getConfig()->getHelperClassName($name))) {
            throw new \InvalidArgumentException("Invalid helper specified '$name'");
        }

        return Mage::helper($name);
    }

    /**
     * @return  array
     */
    public function getParams()
    {
        $params = $this->_getData('params');
        if (is_string($params)) {
            $params = $this->_decode($params);
        }

        return is_array($params) ? $params : array();
    }

    /**
     * @return  MiraklSeller_Process_Model_Process|null
     */
    public function getParent()
    {
        if (!$this->getParentId()) {
            return null;
        }

        return Mage::getModel('mirakl_seller_process/process')->load($this->getParentId());
    }

    /**
     * @param   null|string
     * @return  array|string
     */
    public static function getStatuses()
    {
        static $statuses;
        if (!$statuses) {
            $constants = (new ReflectionClass(get_called_class()))->getConstants();
            $statuses = array_filter(
                $constants, function ($value, $name) {
                    return 0 === strpos($name, 'STATUS_');
                }, ARRAY_FILTER_USE_BOTH
            );
        }

        return array_values($statuses);
    }

    /**
     * @param   bool    $isMirakl
     * @return  string
     */
    public function getStatusClass($isMirakl = false)
    {
        $status = $isMirakl ? $this->getMiraklStatus() : $this->getStatus();

        switch ($status) {
            case self::STATUS_PENDING:
            case self::STATUS_IDLE:
                $class = 'grid-severity-minor';
                break;
            case self::STATUS_PROCESSING:
                $class = 'grid-severity-major';
                break;
            case self::STATUS_STOPPED:
            case self::STATUS_TIMEOUT:
            case self::STATUS_CANCELLED:
            case self::STATUS_ERROR:
                $class = 'grid-severity-critical';
                break;
            case self::STATUS_COMPLETED:
            default:
                $class = 'grid-severity-notice';
        }

        return $class;
    }

    /**
     * Returns process URL for admin
     *
     * @return  string
     */
    public function getUrl()
    {
        return Mage::helper('adminhtml')->getUrl(
            '*/mirakl_seller_process/view', array('id' => $this->getId())
        );
    }

    /**
     * Sets current process status to idle
     *
     * @return  $this
     */
    public function idle()
    {
        return $this->setStatus(self::STATUS_IDLE);
    }

    /**
     * @return  bool
     */
    public function isCancelled()
    {
        return $this->getStatus() == self::STATUS_CANCELLED;
    }

    /**
     * @return  bool
     */
    public function isCompleted()
    {
        return $this->getStatus() === self::STATUS_COMPLETED;
    }

    /**
     * @return  bool
     */
    public function isEnded()
    {
        return $this->isCompleted() || $this->isStopped() || $this->isTimeout()
            || $this->isCancelled() || $this->isError();
    }

    /**
     * @return  bool
     */
    public function isError()
    {
        return $this->getStatus() === self::STATUS_ERROR;
    }

    /**
     * @return  bool
     */
    public function isPending()
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }

    /**
     * @return  bool
     */
    public function isProcessing()
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }

    /**
     * @return  bool
     */
    public function isStatusIdle()
    {
        return $this->getStatus() === self::STATUS_IDLE;
    }

    /**
     * @return  bool
     */
    public function isStopped()
    {
        return $this->getStatus() === self::STATUS_STOPPED;
    }

    /**
     * @return  bool
     */
    public function isTimeout()
    {
        return $this->getStatus() === self::STATUS_TIMEOUT;
    }

    /**
     * Outputs specified string in all associated output handlers
     *
     * @param   string  $str
     * @param   bool    $save
     * @return  $this
     */
    public function output($str, $save = false)
    {
        foreach ($this->_outputs as $output) {
            $output->display($str);
        }

        if ($save) {
            $this->save();
        }

        return $this;
    }

    /**
     * Wraps process execution
     *
     * @param   bool    $force
     * @return  $this
     */
    public function run($force = false)
    {
        if (!$this->isPending() && !$force) {
            Mage::throwException('Cannot run a process that is not in pending status.');
        }

        $parent = $this->getParent();
        if ($parent && !$parent->isCompleted()) {
            Mage::throwException("Parent process #{$parent->getId()} has not completed yet.");
        }

        $this->execute();

        return $this;
    }

    /**
     * Starts current process
     *
     * @return  $this
     */
    public function start()
    {
        $this->_startedAt = microtime(true);

        $this->addOutput('db')
            ->setCreatedAt(time())
            ->setOutput(null)
            ->setDuration(null);

        $this->_registerErrorHandler();

        return $this;
    }

    /**
     * Stops current process
     *
     * @param   string  $status
     * @return  $this
     */
    public function stop($status = self::STATUS_COMPLETED)
    {
        $this->updateDuration();
        $this->setStatus($status);

        // Closing all outputs
        foreach ($this->_outputs as $output) {
            $output->close();
        }

        $this->save();

        restore_error_handler();

        return $this;
    }

    /**
     * Updates current process duration
     *
     * @return  $this
     */
    public function updateDuration()
    {
        if ($this->_startedAt) {
            $duration = ceil(microtime(true) - $this->_startedAt);
            $this->setDuration($duration);
        } elseif ($this->getCreatedAt()) {
            $start = \DateTime::createFromFormat('Y-m-d H:i:s', $this->getCreatedAt());
            $duration = (new \DateTime())->getTimestamp() - $start->getTimestamp();
            $this->setDuration(max(1, $duration)); // 1 second minimum
        }

        return $this;
    }
}
