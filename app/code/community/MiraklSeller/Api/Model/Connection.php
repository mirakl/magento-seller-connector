<?php
/**
 * @method  string  getApiUrl()
 * @method  $this   setApiUrl(string $apiUrl)
 * @method  string  getApiKey()
 * @method  $this   setApiKey(string $apiKey)
 * @method  string  getErrorsCode()
 * @method  $this   setErrorsCode(string $errorsCode)
 * @method  string  getExportedPricesAttribute()
 * @method  $this   setExportedPricesAttribute(string $exportedPricesAttribute)
 * @method  $this   setExportableAttributes(string $exportableAttributes)
 * @method  string  getLastOrdersSynchronizationDate()
 * @method  $this   setLastOrdersSynchronizationDate(string $lastOrdersSynchronizationDate)
 * @method  string  getMagentoTierPricesApplyOn()
 * @method  $this   setMagentoTierPricesApplyOn(string $magentoTierPricesApplyOn)
 * @method  string  getMessagesCode()
 * @method  $this   setMessagesCode(string $messagesCode)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  $this   setOfferAdditionalFields(string $offerAdditionalFields)
 * @method  string  getShopId()
 * @method  $this   setShopId(int $shopId)
 * @method  string  getSkuCode()
 * @method  $this   setSkuCode(string $skuCode)
 * @method  int     getStoreId()
 * @method  $this   setStoreId(int $storeId)
 * @method  string  getSuccessSkuCode()
 * @method  $this   setSuccessSkuCode(string $successSkuCode)
 *
 * @method  $this                                                   load($id, $field = null)
 * @method  MiraklSeller_Api_Model_Resource_Connection_Collection   getCollection()
 * @method  MiraklSeller_Api_Model_Resource_Connection              getResource()
 */
class MiraklSeller_Api_Model_Connection extends Mage_Core_Model_Abstract
{
    const VOLUME_PRICING   = 'VOLUME_PRICING';
    const VOLUME_DISCOUNTS = 'VOLUME_DISCOUNTS';

    /**
     * @var string
     */
    protected $_eventPrefix = 'mirakl_api_connection';

    /**
     * @var string
     */
    protected $_eventObject = 'connection';

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_api/connection');
    }

    /**
     * Returns the connection base URL in order to build Mirakl URLs easily from it
     *
     * @return  string|false
     */
    public function getBaseUrl()
    {
        if (!$apiUrl = $this->getApiUrl()) {
            return false;
        }

        $parts = parse_url($apiUrl);
        $url = sprintf('%s://%s', $parts['scheme'], $parts['host']);

        return $url;
    }

    /**
     * @return  array
     */
    public function getOfferAdditionalFields()
    {
        $fields = $this->_getData('offer_additional_fields');
        if (empty($fields)) {
            $fields = array();
        } elseif (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        return $fields;
    }

    /**
     * @return  array
     */
    public function getExportableAttributes()
    {
        $fields = $this->_getData('exportable_attributes');
        if (empty($fields)) {
            $fields = array();
        } elseif (is_string($fields)) {
            $fields = json_decode($fields, true);
        }

        return $fields;
    }

    /**
     * Validates a connection
     *
     * @return  void
     * @throws  Mage_Core_Exception
     */
    public function validate()
    {
        $helper = Mage::helper('mirakl_seller_api');

        try {
            Mage::helper('mirakl_seller_api/shop')->getAccount($this);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            switch ($e->getCode()) {
                case 401:
                    Mage::throwException($helper->__('CONN-03: You are not authorized to use the API. Please check your API key.'));
                    break;
                case 404:
                    Mage::throwException($helper->__('CONN-02: The API cannot be reached. Please check the API URL.'));
                    break;
                default:
                    Mage::throwException($helper->__('CONN-01: Unexpected system error. Mirakl cannot be reached.'));
            }
        } catch (\Exception $e) {
            Mage::throwException($helper->__('An error occurred: ' . $e->getMessage()));
        }
    }
}
