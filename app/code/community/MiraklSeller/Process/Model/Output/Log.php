<?php

class MiraklSeller_Process_Model_Output_Log extends MiraklSeller_Process_Model_Output_Abstract
{
    /**
     * @var string
     */
    protected $_fileName = 'mirakl_seller_process.log';

    /**
     * @var int
     */
    protected $_level = Zend_Log::INFO;

    /**
     * @var bool
     */
    protected $_force = false;

    /**
     * @return  string
     */
    public function getFileName()
    {
        return $this->_fileName;
    }

    /**
     * @param   string  $fileName
     * @return  $this
     */
    public function setFileName($fileName)
    {
        $this->_fileName = $fileName;

        return $this;
    }

    /**
     * @return  bool
     */
    public function getForce()
    {
        return $this->_force;
    }

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setForce($flag)
    {
        $this->_force = (bool) $flag;

        return $this;
    }

    /**
     * @return  int
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * @param   int $level
     * @return  $this
     */
    public function setLevel($level)
    {
        $this->_level = $level;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        Mage::log($str, $this->_level, $this->_fileName, $this->_force);

        return $this;
    }
}
