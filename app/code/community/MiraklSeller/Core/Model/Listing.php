<?php
/**
 * @method  $this   setBuilderModel(string $builderModel)
 * @method  $this   setBuilderParams(string|array $builderParams)
 * @method  int     getConnectionId()
 * @method  $this   setConnectionId(int $connectionId)
 * @method  bool    getIsActive()
 * @method  $this   setIsActive(bool $flag)
 * @method  string  getLastExportDate()
 * @method  $this   setLastExportDate(string $lastExportDate)
 * @method  string  getName()
 * @method  $this   setName(string $name)
 * @method  $this   setOfferAdditionalFieldsValues(string $offerAdditionalFieldsValues)
 * @method  int     getOfferState()
 * @method  $this   setOfferState(int $offerState)
 * @method  string  getProductIdType()
 * @method  $this   setProductIdType(string $productIdType)
 * @method  string  getProductIdValueAttribute()
 * @method  $this   setProductIdValueAttribute(string $productIdValueAttribute)
 * @method  $this   setVariantsAttributes(string $variantsAttributes)
 *
 * @method  MiraklSeller_Core_Model_Resource_Listing_Collection getCollection()
 * @method  MiraklSeller_Core_Model_Resource_Listing            getResource()
 * @method  MiraklSeller_Core_Model_Listing                     load($id, $field = null)
 */
class MiraklSeller_Core_Model_Listing extends Mage_Core_Model_Abstract
{
    /**
     * Builder is used to fetch products matching the current listing
     */
    const DEFAULT_BUILDER_MODEL = 'mirakl_seller/listing_builder_standard';

    /**
     * Constants used to filter listing products/offers to export
     */
    const TYPE_ALL             = 'ALL';
    const TYPE_PRODUCT         = 'PRODUCT';
    const TYPE_OFFER           = 'OFFER';

    /**
     * Additional constants used to filter listing products to export
     */
    const PRODUCT_MODE_PENDING = 'PENDING';
    const PRODUCT_MODE_ERROR   = 'ERROR';
    const PRODUCT_MODE_ALL     = 'ALL';

    /**
     * @var MiraklSeller_Api_Model_Connection
     */
    protected $_connection;

    /**
     * @var array|null
     */
    protected $_productIds;

    /**
     * @var string
     */
    protected $_decodeMethod = 'unserialize';

    /**
     * Initialize model
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller/listing');
    }

    /**
     * @param   string  $str
     * @return  mixed
     */
    protected function _decode($str)
    {
        return call_user_func($this->_decodeMethod, $str);
    }

    /**
     * Returns array of product ids for current listing
     *
     * @return  int[]
     */
    public function build()
    {
        return $this->getBuilder()->build($this);
    }

    /**
     * @return  bool
     */
    public function isActive()
    {
        return (bool) $this->getIsActive();
    }

    /**
     * @return  array
     */
    public static function getAllowedTypes()
    {
        return array(
            self::TYPE_ALL,
            self::TYPE_PRODUCT,
            self::TYPE_OFFER,
        );
    }

    /**
     * @return  array
     */
    public static function getAllowedProductModes()
    {
        return array(
            self::PRODUCT_MODE_PENDING,
            self::PRODUCT_MODE_ERROR,
            self::PRODUCT_MODE_ALL,
        );
    }

    /**
     * @return  MiraklSeller_Core_Model_Listing_Builder_Interface
     */
    public function getBuilder()
    {
        $builder = Mage::getSingleton($this->getBuilderModel());
        if (!$builder instanceof MiraklSeller_Core_Model_Listing_Builder_Interface) {
            Mage::throwException('Listing builder must implement MiraklSeller_Core_Model_Listing_Builder_Interface');
        }

        return $builder;
    }

    /**
     * @return  string
     */
    public function getBuilderModel()
    {
        $model = $this->_getData('builder_model');
        if (empty($model)) {
            $model = static::DEFAULT_BUILDER_MODEL;
        }

        return $model;
    }

    /**
     * @return  array
     */
    public function getBuilderParams()
    {
        $params = $this->_getData('builder_params');
        if (is_string($params)) {
            $params = $this->_decode($params);
        }

        return is_array($params) ? $params : array();
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getConnection()
    {
        if (null === $this->_connection && $this->getConnectionId()) {
            $this->_connection = Mage::getModel('mirakl_seller_api/connection')
                ->load($this->getConnectionId());
        }

        return $this->_connection;
    }

    /**
     * Proxy to connection's method
     *
     * @return  array
     */
    public function getOfferAdditionalFields()
    {
        if ($connection = $this->getConnection()) {
            return $connection->getOfferAdditionalFields();
        }

        return array();
    }

    /**
     * @return  array
     */
    public function getVariantsAttributes()
    {
        $values = $this->_getData('variants_attributes');
        if (is_string($values)) {
            $values = $this->_decode($values);
        }

        return is_array($values) ? $values : array();
    }

    /**
     * @return  array
     */
    public function getOfferAdditionalFieldsValues()
    {
        $values = $this->_getData('offer_additional_fields_values');
        if (is_string($values)) {
            $values = $this->_decode($values);
        }

        return is_array($values) ? $values : array();
    }

    /**
     * @return  array
     */
    public function getProductIds()
    {
        if (null === $this->_productIds) {
            $this->_productIds = Mage::getResourceModel('mirakl_seller/offer')
                ->getListingProductIds($this->getId());
        }

        return $this->_productIds;
    }

    /**
     * @return  int
     */
    public function getStoreId()
    {
        return $this->getConnection()
            ? $this->getConnection()->getStoreId()
            : Mage_Core_Model_App::ADMIN_STORE_ID;
    }

    /**
     * Returns website associated with the current listing
     *
     * @return  int
     */
    public function getWebsiteId()
    {
        if ($this->getStoreId() != Mage_Core_Model_App::ADMIN_STORE_ID) {
            // Get website of listing's associated store
            $websiteId = Mage::app()->getStore($this->getStoreId())->getWebsiteId();
        } else {
            // Get website of the default store view
            $defaultStore = Mage::app()->getDefaultStoreView();
            $websiteId = $defaultStore->getWebsiteId();
        }

        return $websiteId;
    }

    /**
     * @param   array   $productIds
     * @return  $this
     */
    public function setProductIds(array $productIds)
    {
        $this->_productIds = $productIds;

        return $this;
    }

    /**
     * @throws  Mage_Core_Exception
     */
    public function validate()
    {
        if (!$this->getId()) {
            Mage::throwException('This listing no longer exists.');
        }

        if (!$this->isActive()) {
            Mage::throwException('This listing is inactive.');
        }
    }
}
