<?php

abstract class MiraklSeller_Process_Model_Output_Abstract implements MiraklSeller_Process_Model_Output_Interface
{
    /**
     * @var MiraklSeller_Process_Model_Process
     */
    protected $_process;

    /**
     * @param   MiraklSeller_Process_Model_Process    $process
     */
    public function __construct(MiraklSeller_Process_Model_Process $process)
    {
        $this->_process = $process;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (!$this->_process->isStopped()) {
            $helper = Mage::helper('mirakl_seller_process');
            $this->display($helper->__('Memory Peak Usage: %s', $helper->formatSize(memory_get_peak_usage(true))));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        $class = get_class($this);

        return strtolower(substr($class, strrpos($class, '_') + 1));
    }
}
