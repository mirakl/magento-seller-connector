<?php

use MiraklSeller_Api_Model_Connection as Connection;
use MiraklSeller_Process_Model_Process as Process;

class MiraklSeller_Sales_Model_Cron_Order
{
    /**
     * @return  void
     */
    public function acceptAll()
    {
        if (!Mage::helper('mirakl_seller_sales/config')->isAutoAcceptOrdersEnabled()) {
            return; // Do not do anything if auto accept is off
        }

        $connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();
        foreach ($connections as $connection) {
            $this->_acceptConnection($connection);
        }
    }

    /**
     * @param   Connection  $connection
     * @return  Process
     */
    protected function _acceptConnection(Connection $connection)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType(Process::TYPE_CRON)
            ->setName('Accept Mirakl orders')
            ->setHelper('mirakl_seller_sales/order_process')
            ->setMethod('acceptConnectionOrders')
            ->setParams(array($connection->getId()))
            ->save();

        return $process;
    }

    /**
     * @return  void
     */
    public function synchronizeAll()
    {
        if (!Mage::helper('mirakl_seller_sales/config')->isAutoOrdersImport()) {
            return; // Do not do anything if auto import is off
        }

        $connections = Mage::getModel('mirakl_seller_api/connection')->getCollection();
        foreach ($connections as $connection) {
            $this->_synchronizeConnection($connection);
        }
    }

    /**
     * @param   Connection  $connection
     * @return  Process
     */
    protected function _synchronizeConnection(Connection $connection)
    {
        $process = Mage::getModel('mirakl_seller_process/process')
            ->setType(Process::TYPE_CRON)
            ->setName('Synchronize Mirakl orders')
            ->setHelper('mirakl_seller_sales/order_process')
            ->setMethod('synchronizeConnection')
            ->setParams(array($connection->getId()))
            ->save();

        return $process;
    }
}