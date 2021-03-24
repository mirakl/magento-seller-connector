<?php

use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\Shop\Domain\Collection\Reason\ReasonCollection;
use Mirakl\MMP\Shop\Request\Reason\GetTypeReasonsRequest;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Helper_Reason extends MiraklSeller_Api_Helper_Client_MMP
{
    /**
     * (RE02) Fetches reasons by type
     *
     * @param   Connection  $connection
     * @param   string      $type
     * @param   string|null $locale
     * @return  ReasonCollection
     */
    public function getTypeReasons(Connection $connection, $type = ReasonType::ORDER_MESSAGING, $locale = null)
    {
        $request = new GetTypeReasonsRequest($type);
        $request->setLocale($this->_validateLocale($connection, $locale));

        return $this->send($connection, $request);
    }
}
