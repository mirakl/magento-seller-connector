<?php

use Mirakl\MMP\Shop\Domain\Shop\ShopAccount;
use Mirakl\MMP\Shop\Request\Offer\GetAccountRequest;

class MiraklSeller_Api_Helper_Shop extends MiraklSeller_Api_Helper_Client_MMP
{
    /**
     * (A01) Get shop information 
     *
     * @param   MiraklSeller_Api_Model_Connection   $connection
     * @return  ShopAccount
     */
    public function getAccount(MiraklSeller_Api_Model_Connection $connection)
    {
        return $this->send($connection, new GetAccountRequest());
    }
}
