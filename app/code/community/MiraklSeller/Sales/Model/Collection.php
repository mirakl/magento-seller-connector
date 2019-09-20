<?php

class MiraklSeller_Sales_Model_Collection extends Varien_Data_Collection
{
    /**
     * @param   int $count
     * @return  $this
     */
    public function setTotalRecords($count)
    {
        $this->_totalRecords = (int) $count;

        return $this;
    }
}