<?php

class MiraklSeller_Core_Helper_Inventory extends MiraklSeller_Core_Helper_Data
{
    /**
     * @var bool
     */
    protected $_flagEnableQtyIncrements = false;

    /**
     * @var bool
     */
    protected $_configEnableQtyIncrements;

    /**
     * @var bool
     */
    protected $_flagMinSaleQuantity = false;

    /**
     * @var mixed
     */
    protected $_configMinSaleQuantity;

    /**
     * @var bool
     */
    protected $_flagMaxSaleQuantity = false;

    /**
     * @var mixed
     */
    protected $_configMaxSaleQuantity;

    /**
     * @var bool
     */
    protected $_flagQtyIncrements = false;

    /**
     * @var mixed
     */
    protected $_configQtyIncrements;

    /**
     * @return  bool
     */
    protected function _getConfigEnableQtyIncrements()
    {
        if (!$this->_flagEnableQtyIncrements) {
            $this->_configEnableQtyIncrements = (bool) Mage::getStoreConfig(
                Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ENABLE_QTY_INCREMENTS
            );

            $this->_flagEnableQtyIncrements = true;
        }

        return $this->_configEnableQtyIncrements;
    }

    /**
     * @return  mixed
     */
    protected function _getConfigMaxSaleQuantity()
    {
        if (!$this->_flagMaxSaleQuantity) {
            $this->_configMaxSaleQuantity = Mage::getStoreConfig(
                Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MAX_SALE_QTY
            );

            $this->_flagMaxSaleQuantity = true;
        }

        return $this->_configMaxSaleQuantity;
    }

    /**
     * @return  mixed
     */
    protected function _getConfigMinSaleQuantity()
    {
        if (!$this->_flagMinSaleQuantity) {
            $this->_configMinSaleQuantity = Mage::helper('cataloginventory/minsaleqty')
                ->getConfigValue(Mage::helper('mirakl_seller/config')->getCustomerGroup());

            $this->_flagMinSaleQuantity = true;
        }

        return $this->_configMinSaleQuantity;
    }

    /**
     * @return  mixed
     */
    protected function _getConfigQtyIncrements()
    {
        if (!$this->_flagQtyIncrements) {
            $this->_configQtyIncrements = Mage::getStoreConfig(
                Mage_CatalogInventory_Model_Stock_Item::XML_PATH_QTY_INCREMENTS
            );

            $this->_flagQtyIncrements = true;
        }

        return $this->_configQtyIncrements;
    }

    /**
     * @param   bool    $useConfig
     * @param   float   $productValue
     * @return  float|null
     */
    public function getMaxSaleQuantity($useConfig, $productValue)
    {
        $val = $useConfig ? $this->_getConfigMaxSaleQuantity() : $productValue;

        return (float) $val ?: null;
    }

    /**
     * @param   bool    $useConfig
     * @param   float   $productValue
     * @return  float|null
     */
    public function getMinSaleQuantity($useConfig, $productValue)
    {
        $val = $useConfig ? $this->_getConfigMinSaleQuantity() : $productValue;

        return (float) $val ?: null;
    }

    /**
     * @param   bool    $useConfig
     * @param   float   $productValue
     * @return  float|null
     */
    public function getQtyIncrements($useConfig, $productValue)
    {
        $val = $useConfig ? $this->_getConfigQtyIncrements() : $productValue;

        return (float) $val ?: null;
    }

    /**
     * @param   bool    $useConfig
     * @param   bool    $productValue
     * @return  bool
     */
    public function isEnabledQtyIncrements($useConfig, $productValue)
    {
        $val = $useConfig ? $this->_getConfigEnableQtyIncrements() : $productValue;

        return (bool) $val;
    }
}