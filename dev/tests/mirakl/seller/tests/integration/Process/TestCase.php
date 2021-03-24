<?php
namespace Mirakl\Test\Integration\Process;

use AspectMock\Test;

abstract class TestCase extends \Mirakl\Test\Integration\TestCase
{
    /**
     * @var array
     */
    protected $_processIds = [];

    protected function tearDown()
    {
        if (!empty($this->_processIds)) {
            // Delete created processes
            \Mage::getModel('mirakl_seller_process/process')->getCollection()
                ->addIdFilter($this->_processIds)
                ->walk('delete');
        }

        Test::clean();
    }

    /**
     * @param   mixed   $helperMock
     * @return  \AspectMock\Proxy\Verifier
     */
    public function mockHelperInstance($helperMock)
    {
        return Test::double(\MiraklSeller_Process_Model_Process::class, [
            'getHelperInstance' => $helperMock
        ]);
    }

    /**
     * @param   int|null    $parentId
     * @return  \MiraklSeller_Process_Model_Process
     */
    protected function _createSampleProcess($parentId = null)
    {
        /** @var \MiraklSeller_Process_Model_Process $process */
        $process = \Mage::getModel('mirakl_seller_process/process');
        $process->setType('TESTS')
            ->setName('Sample process for integration tests')
            ->setHelper('mirakl_seller/tests')
            ->setMethod('run')
            ->setParams(['foo', ['bar']])
            ->setParentId($parentId)
            ->save();

        $this->_processIds[] = $process->getId();

        return $process;
    }

    /**
     * @param   int $processId
     * @return  \MiraklSeller_Process_Model_Process
     */
    protected function _getProcessById($processId)
    {
        return \Mage::getModel('mirakl_seller_process/process')->load($processId);
    }
}