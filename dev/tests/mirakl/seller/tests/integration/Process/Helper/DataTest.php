<?php
namespace Mirakl\Test\Integration\Process\Helper;

use Mirakl\Test\Integration\Process;

/**
 * @group process
 * @group helper
 * @coversDefaultClass \MiraklSeller_Process_Helper_Data
 */
class DataTest extends Process\TestCase
{
    /**
     * @var \MiraklSeller_Process_Helper_Data
     */
    protected $_helper;

    protected function setUp()
    {
        parent::setUp();
        $this->_helper = \Mage::helper('mirakl_seller_process');
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testNoPendingProcessFound()
    {
        // No process is present in db, no pending process should be found
        $this->assertNull($this->_helper->getPendingProcess());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetOlderPendingProcess()
    {
        // Create 2 sample processes for test
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess();

        // Ensure that both processes are in pending status
        $this->assertTrue($process1->isPending());
        $this->assertTrue($process2->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->_helper->getPendingProcess();

        // Ensure that process #1 is the pending process because older than process #2
        $this->assertNotNull($pendingProcess);
        $this->assertEquals($process1->getId(), $pendingProcess->getId());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWithParentCompleted()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         */
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess($process1->getId());

        // Ensure that both processes are in pending status
        $this->assertTrue($process1->isPending());
        $this->assertTrue($process2->isPending());

        // Ensure that process #2 is a child of process #1
        $this->assertEquals($process1->getId(), $process2->getParentId());

        $helperMock = new class {
            public function run(\MiraklSeller_Process_Model_Process $process)
            {
                $process->output('This is a test');
            }
        };

        // Run process #1 in order to mark it as completed
        $verifier = self::mockHelperInstance($helperMock);
        $process1->run();
        $verifier->verifyInvokedOnce('getHelperInstance');

        // Ensure that process #1 has completed
        $this->assertTrue($process1->isCompleted());

        // Retrieve real pending process
        $pendingProcess = $this->_helper->getPendingProcess();

        // Ensure that process #2 is the pending process
        $this->assertNotNull($pendingProcess);
        $this->assertEquals($process2->getId(), $pendingProcess->getId());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWhenParentHasFailed()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         * process #3
         */
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess($process1->getId());
        $process3 = $this->_createSampleProcess();

        // Do not use fail() method in order to not cancel children automatically
        $process1->stop(\MiraklSeller_Process_Model_Process::STATUS_ERROR);

        // Ensure that process #2 and #3 are in pending process
        $this->assertTrue($process2->isPending());
        $this->assertTrue($process3->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->_helper->getPendingProcess();

        // Ensure that process #3 is the pending process because #1 is the parent of #2 and has failed
        $this->assertNotNull($pendingProcess);
        $this->assertEquals($process3->getId(), $pendingProcess->getId());
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWhenParentHasFailedInCascade()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         *      |_ process #3
         */
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess($process1->getId());
        $process3 = $this->_createSampleProcess($process2->getId());

        // Do not use fail() method in order to not cancel children automatically
        $process1->stop(\MiraklSeller_Process_Model_Process::STATUS_ERROR);

        // Ensure that process #2 and #3 are in pending process
        $this->assertTrue($process2->isPending());
        $this->assertTrue($process3->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->_helper->getPendingProcess();

        // Ensure that no pending process is found because no parent has completed
        $this->assertNull($pendingProcess);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testCannotGetPendingProcessWithTheSameHash()
    {
        // Create 2 sample processes for test
        $process1 = $this->_createSampleProcess();
        $process1->setStatus(\MiraklSeller_Process_Model_Process::STATUS_PROCESSING);
        $process1->save();
        $process2 = $this->_createSampleProcess();

        // Ensure that statuses are correct
        $this->assertTrue($process1->isProcessing());
        $this->assertTrue($process2->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->_helper->getPendingProcess();

        // We should not have a pending process because processes have the same hash
        $this->assertNull($pendingProcess);
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcessWithDifferentHash()
    {
        // Create 2 sample processes for test
        $process1 = $this->_createSampleProcess();
        $process1->setStatus(\MiraklSeller_Process_Model_Process::STATUS_PROCESSING)
            ->setHash(md5(uniqid()))
            ->save();
        $process2 = $this->_createSampleProcess();

        // Ensure that statuses are correct
        $this->assertTrue($process1->isProcessing());
        $this->assertTrue($process2->isPending());

        // Retrieve real pending process
        $pendingProcess = $this->_helper->getPendingProcess();

        // Process #2 is the pending process because hash is different
        $this->assertNotNull($pendingProcess);
        $this->assertEquals($process2->getId(), $pendingProcess->getId());
    }
}