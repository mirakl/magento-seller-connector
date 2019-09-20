<?php

class MiraklSeller_Process_Model_Output_Db extends MiraklSeller_Process_Model_Output_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        if ($this->_process) {
            $this->_process->setOutput(
                trim($this->_process->getOutput() . "\n" . $str)
            );
        }

        return $this;
    }
}
