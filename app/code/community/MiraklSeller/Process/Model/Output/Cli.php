<?php

class MiraklSeller_Process_Model_Output_Cli extends MiraklSeller_Process_Model_Output_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        printf('%s%s', $str, PHP_EOL);
        @ob_flush();

        return $this;
    }
}
