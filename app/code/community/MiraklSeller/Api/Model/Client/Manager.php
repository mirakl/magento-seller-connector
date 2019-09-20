<?php

use Mirakl\Core\Client\AbstractApiClient;

class MiraklSeller_Api_Model_Client_Manager
{
    /**
     * @var AbstractApiClient[]
     */
    private static $_clients = array();

    /**
     * Disable all API clients
     */
    public static function disable()
    {
        foreach (self::$_clients as $client) {
            $client->disable();
        }
    }

    /**
     * Enable all API clients
     */
    public static function enable()
    {
        foreach (self::$_clients as $client) {
            $client->disable(false);
        }
    }

    /**
     * @param   MiraklSeller_Api_Model_Connection   $connection
     * @param   string                              $area
     * @return  AbstractApiClient
     */
    public function get($connection, $area)
    {
        $hash = sha1(json_encode(array($connection->getId(), $area)));
        if (!isset(self::$_clients[$hash])) {
            $clientFactory = Mage::getModel('mirakl_seller_api/client_factory');
            self::$_clients[$hash] = $clientFactory->create(
                $connection->getApiUrl(),
                $connection->getApiKey(),
                $area,
                $connection->getShopId() ?: null
            );
        }

        return self::$_clients[$hash];
    }
}
