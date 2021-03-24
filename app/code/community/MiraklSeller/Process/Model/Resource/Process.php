<?php

class MiraklSeller_Process_Model_Resource_Process extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * @var array
     */
    protected $_serializableFields = array(
        'params' => array(null, array())
    );

    /**
     * @var string
     */
    protected $_encodeMethod = 'md5';

    /**
     * Initialize model and primary key field
     */
    protected function _construct()
    {
        $this->_init('mirakl_seller_process/process', 'id');
    }

    /**
     * @param   string  $str
     * @return  mixed
     */
    protected function _encode($str)
    {
        return call_user_func($this->_encodeMethod, $str);
    }

    /**
     * @param   Mage_Core_Model_Abstract    $object
     * @return  array
     */
    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        /** @var MiraklSeller_Process_Model_Process $object */
        if (!$object->getHash()) {
            $object->setHash($this->_encode($object->getType() . ' ' . $object->getName()));
        }

        if (!$object->getStatus()) {
            $object->setStatus(\MiraklSeller_Process_Model_Process::STATUS_PENDING);
        }

        $currentTime = Varien_Date::now();
        if ((!$object->getId() || $object->isObjectNew()) && !$object->getCreatedAt()) {
            $object->setCreatedAt($currentTime);
        }

        $object->setUpdatedAt($currentTime);
        $data = parent::_prepareDataForSave($object);

        return $data;
    }

    /**
     * Deletes specified processes from database
     *
     * @param   array   $ids
     * @return  bool|int
     */
    public function deleteIds(array $ids)
    {
        if (!empty($ids)) {
            return $this->_getWriteAdapter()->delete($this->getMainTable(), array('id IN (?)' => $ids));
        }

        return false;
    }

    /**
     * Mark expired processes execution as TIMEOUT according to specified delay in minutes
     *
     * @param   int $delay
     * @return  int $result
     */
    public function markAsTimeout($delay)
    {
        $delay = abs((int) $delay);
        if (!$delay) {
            Mage::throwException('Delay for expired processes cannot be empty');
        }

        $now = Varien_Date::now();
        $timestampDiffExpr = new Zend_Db_Expr(
            sprintf(
                "TIMESTAMPDIFF(MINUTE, created_at, '%s') > %d",
                $now,
                $delay
            )
        );

        $result = $this->_getWriteAdapter()->update(
            $this->getMainTable(),
            array(
                'status' => MiraklSeller_Process_Model_Process::STATUS_TIMEOUT,
                'updated_at' => $now,
            ),
            array(
                'status = ?' => MiraklSeller_Process_Model_Process::STATUS_PROCESSING,
                strval($timestampDiffExpr) => MiraklSeller_Process_Model_Process::STATUS_TIMEOUT
            )
        );

        return $result;
    }

    /**
     * Truncate mirakl_seller_process table
     */
    public function truncate()
    {
        $this->_getWriteAdapter()->truncateTable($this->getMainTable());
    }
}
