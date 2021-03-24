<?php

interface MiraklSeller_Core_Model_Listing_Download_Adapter_Interface
{
    /**
     * Returns file contents
     *
     * @return  string
     */
    public function getContents();

    /**
     * @return  string
     */
    public function getFileExtension();

    /**
     * Writes data to file
     *
     * @param   array   $data
     * @return  int
     */
    public function write(array $data);
}