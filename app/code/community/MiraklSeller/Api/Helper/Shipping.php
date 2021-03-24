<?php

use Mirakl\MMP\Common\Domain\Collection\Shipping\CarrierCollection;
use Mirakl\MMP\Shop\Request\Shipping\GetShippingCarriersRequest;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Helper_Shipping extends MiraklSeller_Api_Helper_Client_MMP
{
    /**
     * (SH21) List all carriers (sorted by sortIndex, defined in the BO)
     *
     * @param   Connection  $connection
     * @return  CarrierCollection
     */
    public function getCarriers(Connection $connection)
    {
        $request = new GetShippingCarriersRequest();

        return $this->send($connection, $request);
    }
}
