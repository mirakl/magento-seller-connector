<?php

require_once 'Psr/Log/LoggerInterface.php';
require_once 'Psr/Log/LogLevel.php';

use Psr\Log\LogLevel;

class MiraklSeller_Api_Model_Log_Logger implements \Psr\Log\LoggerInterface
{
    /**
     * @var string
     */
    protected $logFile = 'mirakl_seller_api.log';

    /**
     * @var array
     */
    protected $logLevelMapping = [
        LogLevel::EMERGENCY => Zend_Log::EMERG,
        LogLevel::ALERT     => Zend_Log::ALERT,
        LogLevel::CRITICAL  => Zend_Log::CRIT,
        LogLevel::ERROR     => Zend_Log::ERR,
        LogLevel::WARNING   => Zend_Log::WARN,
        LogLevel::NOTICE    => Zend_Log::NOTICE,
        LogLevel::INFO      => Zend_Log::INFO,
        LogLevel::DEBUG     => Zend_Log::DEBUG,
    ];

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = array())
    {
        $this->log(Zend_Log::EMERG, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = array())
    {
        $this->log(Zend_Log::ALERT, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = array())
    {
        $this->log(Zend_Log::CRIT, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = array())
    {
        $this->log(Zend_Log::ERR, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = array())
    {
        $this->log(Zend_Log::WARN, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = array())
    {
        $this->log(Zend_Log::NOTICE, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = array())
    {
        $this->log(Zend_Log::INFO, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = array())
    {
        $this->log(Zend_Log::DEBUG, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = array())
    {
        if (isset($this->logLevelMapping[$level])) {
            $level = $this->logLevelMapping[$level];
        }

        Mage::log($message, $level, $this->logFile, true);
    }

    /**
     * @return  string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }

    /**
     * @param   string  $file
     * @return  $this
     */
    public function setLogFile($file)
    {
        $this->logFile = (string) $file;

        return $this;
    }

    /**
     * @return  string
     */
    public function getLogFilePath()
    {
        return Mage::getBaseDir('var') . DS . 'log' . DS . $this->logFile;
    }

    /**
     * Clears log file contents
     *
     * @return  void
     */
    public function clear()
    {
        if ($this->getLogFileSize()) {
            file_put_contents($this->getLogFilePath(), '');
        }
    }

    /**
     * @return  string
     */
    public function getLogFileContents()
    {
        if (file_exists($this->getLogFilePath())) {
            return file_get_contents($this->getLogFilePath());
        }

        return '';
    }

    /**
     * @return  int
     */
    public function getLogFileSize()
    {
        if (file_exists($this->getLogFilePath())) {
            return filesize($this->getLogFilePath());
        }

        return 0;
    }

    /**
     * @return  \GuzzleHttp\MessageFormatter
     */
    public function getMessageFormatter()
    {
        switch (Mage::helper('mirakl_seller_api/config')->getApiLogOption()) {
            case MiraklSeller_Api_Model_Log_Options::LOG_REQUESTS_ONLY:
                $format = ">>>>>>>>\n{request}\n--------\n{error}";
                break;

            case MiraklSeller_Api_Model_Log_Options::LOG_ALL:
            default:
                $format = \GuzzleHttp\MessageFormatter::DEBUG;
        }

        return new \GuzzleHttp\MessageFormatter($format);
    }
}