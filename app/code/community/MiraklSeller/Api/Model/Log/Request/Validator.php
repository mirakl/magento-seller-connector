<?php

use Mirakl\Core\Request\RequestInterface;

class MiraklSeller_Api_Model_Log_Request_Validator
{
    /**
     * @var MiraklSeller_Api_Helper_Config
     */
    protected $config;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->config = Mage::helper('mirakl_seller_api/config');
    }

    /**
     * @param   RequestInterface    $request
     * @return  string
     */
    private function getRequestUrl(RequestInterface $request)
    {
        $query = '';
        $queryParams = $request->getQueryParams();
        if (!empty($queryParams)) {
            $query = '?' . http_build_query($queryParams, null, '&', PHP_QUERY_RFC3986);
        }

        return 'api/' . urldecode($request->getUri() . $query);
    }

    /**
     * @param   RequestInterface    $request
     * @return  bool
     */
    public function validate(RequestInterface $request)
    {
        if (!$this->config->isApiLogEnabled()) {
            return false;
        }

        $filterPattern = $this->config->getApiLogFilter();

        if (empty($filterPattern)) {
            return true;
        }

        $filterPattern = '#' . trim($filterPattern, '#/') . '#i';
        $filterPattern = str_replace('/', '\/', $filterPattern);

        return 1 === preg_match($filterPattern, $this->getRequestUrl($request));
    }
}