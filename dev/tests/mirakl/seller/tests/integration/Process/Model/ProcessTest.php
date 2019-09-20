<?php
namespace Mirakl\Test\Integration\Process\Model;

use Mirakl\Test\Integration\Process;

/**
 * @group process
 * @group model
 * @coversDefaultClass \MiraklSeller_Process_Model_Process
 */
class ProcessTest extends Process\TestCase
{
    /**
     * @covers ::run
     */
    public function testRunProcessWithParams()
    {
        // Create a sample process for test
        $process = $this->_createSampleProcess();

        // Mock the process helper method for test
        $helperMock = new class {
            public function run(\MiraklSeller_Process_Model_Process $process, $foo, $bar)
            {
                $process->output('This is a test');
                Process\TestCase::assertTrue($process->isProcessing());
                Process\TestCase::assertSame('foo', $foo);
                Process\TestCase::assertSame(['bar'], $bar);
            }
        };

        // Ensure that process has been saved correctly in pending status and with params
        $this->assertNotEmpty($process->getId());
        $this->assertTrue($process->isPending());
        $this->assertNull($process->getParentId());
        $this->assertNotEmpty($process->getParams());

        // Run the process
        $verifier = self::mockHelperInstance($helperMock);
        $process->run();
        $verifier->verifyInvokedOnce('getHelperInstance');

        // Process should be completed without any arror
        $this->assertTrue($process->isCompleted());
        $this->assertGreaterThan(0, $process->getDuration());
        $this->assertNotEmpty($process->getOutput());
    }

    /**
     * @covers ::run
     */
    public function testRunProcessWithUserError()
    {
        // Create a sample process for test
        $process = $this->_createSampleProcess();

        // Mock the process helper method for test
        $helperMock = new class {
            public function run()
            {
                trigger_error('This is a sample user error', E_USER_ERROR);
            }
        };

        // Run the process, an error should occurred and mark the process has "error"
        $verifier = self::mockHelperInstance($helperMock);
        $process->run();
        $verifier->verifyInvokedOnce('getHelperInstance');

        // Process must have the status "error" and error message should be logged in process output
        $this->assertTrue($process->isError());
        $this->assertNotEmpty($process->getOutput());
    }

    /**
     * @covers ::run
     */
    public function testRunChildProcessWhenParentIsCompleted()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         */
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess($process1->getId());

        // Mock the process helper method for test
        $helperMock = new class {
            public function run(\MiraklSeller_Process_Model_Process $process)
            {
                $process->output('This is a test');
            }
        };

        // Run both processes one after the other
        $verifier = self::mockHelperInstance($helperMock);
        $process1->run();
        $process2->run();
        $verifier->verifyInvokedMultipleTimes('getHelperInstance', 2);

        // Ensure that both processes have been executed successfully
        $this->assertTrue($process1->isCompleted());
        $this->assertTrue($process2->isCompleted());
    }

    /**
     * @covers ::run
     */
    public function testCannotRunChildProcessIfParentIsNotCompleted()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         */
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess($process1->getId());

        try {
            // Use try/catch in order to be able to test processes status afterwards
            $process2->run();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Mage_Core_Exception::class, $e);
            $this->assertContains('has not completed yet', $e->getMessage());
        }

        // Verify that statuses did not change
        $this->assertTrue($process1->isPending());
        $this->assertTrue($process2->isPending());
    }

    /**
     * @coversNothing
     */
    public function testCancelChildrenProcessesInCascadeWhenParentFails()
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

        $process1->fail('Failing process #1 to test children automatic cascade cancellation');

        // Ensure that process #1 has failed and that other processes have been cancelled in cascade
        $this->assertTrue($process1->isError());
        $this->assertTrue($this->_getProcessById($process2->getId())->isCancelled());
        $this->assertTrue($this->_getProcessById($process3->getId())->isCancelled());
    }

    /**
     * @coversNothing
     */
    public function testDeleteParentProcessMustDeleteChildrenInCascade()
    {
        /**
         * Create sample processes with parent/child dependency for test:
         *
         * process #1
         *  |_ process #2
         *  |_ process #3
         *      |_ process #4
         *  |_ process #5
         */
        $process1 = $this->_createSampleProcess();
        $process2 = $this->_createSampleProcess($process1->getId());
        $process3 = $this->_createSampleProcess($process1->getId());
        $process4 = $this->_createSampleProcess($process3->getId());
        $process5 = $this->_createSampleProcess($process1->getId());

        // Delete the main process should delete all children in cascade
        $process1->delete();

        $this->assertNull($this->_getProcessById($process1->getId())->getId());
        $this->assertNull($this->_getProcessById($process2->getId())->getId());
        $this->assertNull($this->_getProcessById($process3->getId())->getId());
        $this->assertNull($this->_getProcessById($process4->getId())->getId());
        $this->assertNull($this->_getProcessById($process5->getId())->getId());
    }
}