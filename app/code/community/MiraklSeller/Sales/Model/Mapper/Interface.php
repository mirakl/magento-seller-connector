<?php

interface MiraklSeller_Sales_Model_Mapper_Interface
{
    /**
     * @param   array       $data
     * @param   string|null $locale
     * @return  array
     */
    public function map(array $data, $locale = null);
}