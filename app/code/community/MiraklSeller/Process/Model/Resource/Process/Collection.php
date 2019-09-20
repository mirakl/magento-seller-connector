<?php
/**
 * @method  $this                                       addFieldToFilter($field, $condition = null)
 * @method  MiraklSeller_Process_Model_Process          getFirstItem()
 * @method  MiraklSeller_Process_Model_Resource_Process getResource()
 */
class MiraklSeller_Process_Model_Resource_Process_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
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
        $this->_init('mirakl_seller_process/process');
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        /** @var MiraklSeller_Process_Model_Process $item */
        foreach ($this->_items as $item) {
            $this->getResource()->unserializeFields($item);
        }

        return parent::_afterLoad();
    }

    /**
     * Adds API Type filter to current collection
     *
     * @return  $this
     */
    public function addApiTypeFilter()
    {
        return $this->addFieldToFilter('main_table.type', 'API');
    }

    /**
     * Adds completed status filter to current collection
     *
     * @return  $this
     */
    public function addCompletedFilter()
    {
        return $this->addStatusFilter(MiraklSeller_Process_Model_Process::STATUS_COMPLETED);
    }

    /**
     * Excludes processes that have the same hash as the given ones
     *
     * @param   string|array    $hash
     * @return  $this
     */
    public function addExcludeHashFilter($hash)
    {
        if (empty($hash)) {
            return $this;
        }

        if (!is_array($hash)) {
            $hash = array($hash);
        }

        return $this->addFieldToFilter('main_table.hash', array('nin' => $hash));
    }

    /**
     * @param   int|array   $processIds
     * @return  $this
     */
    public function addIdFilter($processIds)
    {
        if (empty($processIds)) {
            return $this;
        }

        if (!is_array($processIds)) {
            $processIds = array($processIds);
        }

        return $this->addFieldToFilter('main_table.id', array('in' => $processIds));
    }

    /**
     * @param   int $parentId
     * @return  $this
     */
    public function addParentFilter($parentId)
    {
        $this->addFieldToFilter('main_table.parent_id', $parentId);

        return $this;
    }

    /**
     * Exclude processes that have to wait for parent process to be completed
     *
     * @return  $this
     */
    public function addParentCompletedFilter()
    {
        $this->joinParent();
        $this->getSelect()
            ->where(
                'main_table.parent_id IS NULL OR parent.status = ?',
                MiraklSeller_Process_Model_Process::STATUS_COMPLETED
            );

        return $this;
    }

    /**
     * Adds idle status filter to current collection
     *
     * @return  $this
     */
    public function addIdleFilter()
    {
        return $this->addStatusFilter(MiraklSeller_Process_Model_Process::STATUS_IDLE);
    }

    /**
     * Adds pending status filter to current collection
     *
     * @return  $this
     */
    public function addPendingFilter()
    {
        return $this->addStatusFilter(MiraklSeller_Process_Model_Process::STATUS_PENDING);
    }

    /**
     * Adds processing status filter to current collection
     *
     * @return  $this
     */
    public function addProcessingFilter()
    {
        return $this->addStatusFilter(MiraklSeller_Process_Model_Process::STATUS_PROCESSING);
    }

    /**
     * Adds processing status filter to current collection for mirakl_status field
     *
     * @return  $this
     */
    public function addMiraklProcessingFilter()
    {
        return $this->addFieldToFilter('main_table.mirakl_status', MiraklSeller_Process_Model_Process::STATUS_PROCESSING);
    }

    /**
     * Adds pending status filter to current collection for mirakl_status field
     *
     * @return  $this
     */
    public function addMiraklPendingFilter()
    {
        return $this->addFieldToFilter('main_table.mirakl_status', MiraklSeller_Process_Model_Process::STATUS_PENDING);
    }

    /**
     * @param   string  $status
     * @return  $this
     */
    public function addStatusFilter($status)
    {
        return $this->addFieldToFilter('main_table.status', $status);
    }

    /**
     * @param   array   $cols
     * @return  $this
     */
    public function joinParent($cols = array())
    {
        $this->getSelect()
            ->joinLeft(
                array('parent' => $this->getMainTable()),
                'main_table.parent_id = parent.id',
                $cols
            );

        return $this;
    }
}
