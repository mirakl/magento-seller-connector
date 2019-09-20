<?php

class MiraklSeller_Api_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_PATH_API_DEVELOPER_LOG_OPTION = 'mirakl_seller_api_developer/log/log_option';
    const XML_PATH_API_DEVELOPER_LOG_FILTER = 'mirakl_seller_api_developer/log/log_filter';

    /**
     * @var bool
     */
    protected $_apiEnabled = true;

    /**
     * @return  $this
     */
    public function disable()
    {
        MiraklSeller_Api_Model_Client_Manager::disable();

        return $this->setApiEnabled(false);
    }

    /**
     * @return  $this
     */
    public function enable()
    {
        MiraklSeller_Api_Model_Client_Manager::enable();

        return $this->setApiEnabled(true);
    }

    /**
     * @return  bool
     */
    public function isEnabled()
    {
        return $this->_apiEnabled;
    }

    /**
     * Enable or disable API
     *
     * @param   bool    $flag
     * @return  $this
     */
    public function setApiEnabled($flag)
    {
        $this->_apiEnabled = (bool) $flag;

        return $this;
    }

    /**
     * @param   mixed   $store
     * @return  int
     */
    public function getApiLogOption($store = null)
    {
        return (int) Mage::getStoreConfig(self::XML_PATH_API_DEVELOPER_LOG_OPTION, $store);
    }

    /**
     * @param   mixed   $store
     * @return  string
     */
    public function getApiLogFilter($store = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_API_DEVELOPER_LOG_FILTER, $store);
    }

    /**
     * @param   mixed   $store
     * @return  bool
     */
    public function isApiLogEnabled($store = null)
    {
        return $this->getApiLogOption($store) !== MiraklSeller_Api_Model_Log_Options::LOG_DISABLED;
    }
}
