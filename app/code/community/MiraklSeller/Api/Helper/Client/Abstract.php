<?php

use Mirakl\Core\Client\AbstractApiClient;
use Mirakl\Core\Request\AbstractRequest;
use MiraklSeller_Api_Model_Connection as Connection;

abstract class MiraklSeller_Api_Helper_Client_Abstract extends MiraklSeller_Api_Helper_Config
{
    /**
     * Proxy to API client methods
     *
     * @param   string  $name
     * @param   array   $args
     * @return  mixed
     */
    public function __call($name, $args)
    {
        $connection = array_shift($args);
        if (!$connection instanceof Connection) {
            throw new \InvalidArgumentException('The first argument must be the connection.');
        }

        return call_user_func_array(array($this->getClient($connection), $name), $args);
    }

    /**
     * @return  string
     */
    abstract protected function _getArea();

    /**
     * Get API client
     *
     * @param   Connection  $connection
     * @return  AbstractApiClient
     */
    public function getClient(Connection $connection)
    {
        return Mage::getModel('mirakl_seller_api/client_manager')->get($connection, $this->_getArea());
    }

    /**
     * @param   Connection      $connection
     * @param   AbstractRequest $request
     * @param   bool            $raw
     * @return  mixed
     */
    public function send(Connection $connection, AbstractRequest $request, $raw = false)
    {
        $client = $this->getClient($connection);
        $client->raw((bool) $raw);

        $requestValidator = Mage::getModel('mirakl_seller_api/log_request_validator');

        if ($requestValidator->validate($request)) {
            $logger = Mage::getSingleton('mirakl_seller_api/log_logger');
            $client->setLogger($logger, $logger->getMessageFormatter());
        }

        Mage::dispatchEvent('mirakl_seller_api_send_request_before', array(
            'client'     => $client,
            'connection' => $connection,
            'request'    => $request,
            'helper'     => $this,
        ));

        return $client($request);
    }
}