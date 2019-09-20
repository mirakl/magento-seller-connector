<?php

use MiraklSeller_Core_Model_Listing as Listing;

interface MiraklSeller_Core_Model_Listing_Export_Interface
{
    /**
     * @param   Listing $listing
     * @return  array
     */
    public function export(Listing $listing);
}