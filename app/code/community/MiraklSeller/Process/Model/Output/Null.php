<?php

class MiraklSeller_Process_Model_Output_Null extends MiraklSeller_Process_Model_Output_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        return $this;
    }
}
