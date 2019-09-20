<?php
/**
 * @method  $this                                       addFieldToFilter($field, $condition = null)
 * @method  MiraklSeller_Core_Model_Listing             getFirstItem()
 * @method  MiraklSeller_Core_Model_Resource_Listing    getResource()
 */
class MiraklSeller_Core_Model_Resource_Listing_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
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
        $this->_init('mirakl_seller/listing');
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        /** @var MiraklSeller_Core_Model_Listing $item */
        foreach ($this->_items as $item) {
            $this->getResource()->unserializeFields($item);
        }

        return parent::_afterLoad();
    }

    /**
     * @param   int|array   $listingIds
     * @return  $this
     */
    public function addIdFilter($listingIds)
    {
        if (empty($listingIds)) {
            return $this;
        }

        if (!is_array($listingIds)) {
            $listingIds = array($listingIds);
        }

        return $this->addFieldToFilter('id', array('in' => $listingIds));
    }

    /**
     * @param   mixed   $connection
     * @return  $this
     */
    public function addConnectionFilter($connection)
    {
        if ($connection instanceof MiraklSeller_Api_Model_Connection) {
            $connection = array($connection->getId());
        }

        if (!is_array($connection)) {
            $connection = array($connection);
        }

        $this->addFilter('connection_id', array('in' => $connection));

        return $this;
    }

    /**
     * @param   boolean   $isActive
     * @return  $this
     */
    public function addActiveFilter($isActive = true)
    {
        return $this->addFieldToFilter('is_active', $isActive);
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
