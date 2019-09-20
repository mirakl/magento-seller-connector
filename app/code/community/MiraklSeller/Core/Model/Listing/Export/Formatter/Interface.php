<?php

use MiraklSeller_Core_Model_Listing as Listing;

interface MiraklSeller_Core_Model_Listing_Export_Formatter_Interface
{
    /**
     * @param   array   $data
     * @param   Listing $listing
     * @return  array
     */
    public function format(array $data, Listing $listing);
}