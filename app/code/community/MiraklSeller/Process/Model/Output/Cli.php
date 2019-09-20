<?php

class MiraklSeller_Process_Model_Output_Cli extends MiraklSeller_Process_Model_Output_Abstract
{
    /**
     * {@inheritdoc}
     */
    public function display($str)
    {
        echo $str . PHP_EOL; // @codingStandardsIgnoreLine
        @ob_flush();

        return $this;
    }
}
