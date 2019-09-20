<?php

use Mirakl\Core\Client\AbstractApiClient;

class MiraklSeller_Api_Model_Client_Factory
{
    /**
     * @var MiraklSeller_Api_Helper_Config
     */
    protected $_config;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_config = Mage::helper('mirakl_seller_api/config');
    }

    /**
     * @param   string  $apiUrl
     * @param   string  $apiKey
     * @param   string  $area
     * @param   int     $shopId
     * @param   int     $timeout
     * @return  AbstractApiClient
     */
    public function create($apiUrl, $apiKey, $area, $shopId = null, $timeout = null)
    {
        switch ($area) {
            case 'MMP':
                $instanceName = \Mirakl\MMP\Shop\Client\ShopApiClient::class;
                break;
            case 'MCI':
                $instanceName = \Mirakl\MCI\Shop\Client\ShopApiClient::class;
                break;
            default:
                throw new \InvalidArgumentException('Could not create API client for area ' . $area);
        }

        /** @var AbstractApiClient $client */
        $client = new $instanceName($apiUrl, $apiKey, $shopId);
        $this->init($client);

        if ($timeout !== null) {
            // Add a connection timeout
            $client->addOption('connect_timeout', $timeout);
        }

        return $client;
    }

    /**
     * @param   AbstractApiClient   $client
     */
    private function init(AbstractApiClient $client)
    {
        // Customize User-Agent
        $userAgent = sprintf(
            'Magento/%s Mirakl-Seller-Connector/%s %s',
            Mage::getVersion(),
            Mage::helper('mirakl_seller')->getVersion(),
            AbstractApiClient::getDefaultUserAgent()
        );
        $client->setUserAgent($userAgent);

        Mage::dispatchEvent('mirakl_seller_api_init_client', array('client' => $client));

        // Disable API calls if needed
        if (!$this->_config->isEnabled()) {
            $client->disable();
        }
    }
}
