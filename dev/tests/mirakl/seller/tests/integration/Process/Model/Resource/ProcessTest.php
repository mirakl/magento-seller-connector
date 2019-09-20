<?php
namespace Mirakl\Test\Integration\Process\Model\Resource;

use Mirakl\Test\Integration\Process;

/**
 * @group process
 * @group model
 * @coversDefaultClass \MiraklSeller_Process_Model_Resource_Process
 */
class ProcessTest extends Process\TestCase
{
    /**
     * @var \MiraklSeller_Process_Model_Resource_Process
     */
    protected $_resourceModel;

    protected function setUp()
    {
        $this->_resourceModel = \Mage::getResourceModel('mirakl_seller_process/process');
    }

    /**
     * @covers ::markAsTimeout
     */
    public function testMarkAsTimeout()
    {
        $process = $this->_createSampleProcess();
        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_PROCESSING);
        $process->setCreatedAt('2017-07-19 05:00:00');
        $process->save();

        $this->_resourceModel->markAsTimeout(10); // 10 minutes

        // Reload process
        $process = $this->_getProcessById($process->getId());

        $this->assertTrue($process->isTimeout());
    }

    /**
     * @covers ::markAsTimeout
     * @expectedException \Mage_Core_Exception
     * @expectedExceptionMessage Delay for expired processes cannot be empty
     */
    public function testMarkAsTimeoutWithEmptyDelay()
    {
        $this->_resourceModel->markAsTimeout(0);
    }
}