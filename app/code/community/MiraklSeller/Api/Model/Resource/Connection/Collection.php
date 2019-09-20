<?php
/**
 * @method  $this                                       addFieldToFilter($field, $condition = null)
 * @method  MiraklSeller_Api_Model_Connection           getItemById($id)
 * @method  MiraklSeller_Api_Model_Connection           getFirstItem()
 * @method  MiraklSeller_Api_Model_Resource_Connection  getResource()
 */
class MiraklSeller_Api_Model_Resource_Connection_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Custom id field
     *
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize model
     */
    public function _construct()
    {
        $this->_init('mirakl_seller_api/connection');
    }

    /**
     * @param   int|array   $connectionIds
     * @return  $this
     */
    public function addIdFilter($connectionIds)
    {
        if (empty($connectionIds)) {
            return $this;
        }

        if (!is_array($connectionIds)) {
            $connectionIds = array($connectionIds);
        }

        return $this->addFieldToFilter('id', array('in' => $connectionIds));
    }

    /**
     * @param   mixed   $store
     * @return  $this
     */
    public function addStoreFilter($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $store = array($store->getId());
        }

        if (!is_array($store)) {
            $store = array($store);
        }

        $this->addFilter('store_id', array('in' => $store));

        return $this;
    }

    /**
     * Get associative array of connection as [id => name]
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->_toOptionArray($this->getIdFieldName(), 'name');
    }
}
