<?php

interface MiraklSeller_Process_Model_Output_Interface
{
    /**
     * @return  $this
     */
    public function close();

    /**
     * @param   string  $str
     * @return  $this
     */
    public function display($str);

    /**
     * @return  string
     */
    public function getType();
}