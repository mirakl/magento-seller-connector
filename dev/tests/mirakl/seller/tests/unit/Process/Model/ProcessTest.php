<?php
namespace Mirakl\Test\Unit\Process\Model;

use PHPUnit\Framework\TestCase;

/**
 * @group process
 * @group model
 * @coversDefaultClass \MiraklSeller_Process_Model_Process
 */
class ProcessTest extends TestCase
{
    public function testGetStatuses()
    {
        $expectedStatuses = [
            'pending',
            'processing',
            'idle',
            'completed',
            'stopped',
            'timeout',
            'cancelled',
            'error',
        ];
        $this->assertSame($expectedStatuses, \MiraklSeller_Process_Model_Process::getStatuses());
    }

    /**
     * @covers ::isEnded
     */
    public function testIsEnded()
    {
        /** @var \MiraklSeller_Process_Model_Process $process */
        $process = \Mage::getModel('mirakl_seller_process/process');

        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_STOPPED);
        $this->assertTrue($process->isEnded());
        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_COMPLETED);
        $this->assertTrue($process->isEnded());
        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_CANCELLED);
        $this->assertTrue($process->isEnded());
        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_ERROR);
        $this->assertTrue($process->isEnded());
        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_TIMEOUT);
        $this->assertTrue($process->isEnded());
        $process->setStatus(\MiraklSeller_Process_Model_Process::STATUS_IDLE);
        $this->assertFalse($process->isEnded());
    }

    /**
     * @covers ::canRun
     */
    public function testCanRun()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['canRun', '__call'])
            ->getMock();
        $processMock->expects($this->exactly(5))
            ->method('getParent')
            ->willReturnOnConsecutiveCalls(null, null, null, $processMock, $processMock);
        $processMock->expects($this->exactly(5))
            ->method('isProcessing')
            ->willReturnOnConsecutiveCalls(true, false, false, false, false);
        $processMock->expects($this->exactly(4))
            ->method('isStatusIdle')
            ->willReturnOnConsecutiveCalls(false, true, false, false);
        $processMock->expects($this->exactly(2))
            ->method('isCompleted')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->assertFalse($processMock->canRun());
        $this->assertTrue($processMock->canRun());
        $this->assertFalse($processMock->canRun());
        $this->assertFalse($processMock->canRun());
        $this->assertTrue($processMock->canRun());
    }

    /**
     * @covers ::run
     * @expectedException \Mage_Core_Exception
     * @expectedExceptionMessage Cannot run a process that is not in pending status
     */
    public function testRun()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['run'])
            ->getMock();
        $processMock->expects($this->exactly(3))
            ->method('isPending')
            ->willReturnOnConsecutiveCalls(true, false, false);
        $processMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnSelf();

        $processMock->run();
        $processMock->run(true);

        $processMock->run();
    }

    /**
     * @covers ::getParent
     */
    public function testGetParent()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getParent', '__call'])
            ->getMock();
        $processMock->expects($this->any())
            ->method('getData')
            ->with($this->equalTo('parent_id'))
            ->willReturnOnConsecutiveCalls(null, 1234);

        $this->assertNull($processMock->getParent());
        $this->assertInstanceOf(\MiraklSeller_Process_Model_Process::class, $processMock->getParent());
    }

    /**
     * @covers ::addOutput
     * @expectedException \Mage_Core_Exception
     * @expectedExceptionMessage Invalid output specified.
     */
    public function testAddOutput()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addOutput'])
            ->getMock();
        $outputMock = $this->createMock(\MiraklSeller_Process_Model_Output_Db::class);

        $this->assertEquals($processMock, $processMock->addOutput('cli')); // no exception expected
        $this->assertEquals($processMock, $processMock->addOutput($outputMock));  // no exception expected

        $processMock->addOutput($this->anything());
    }

    /**
     * @covers ::execute
     */
    public function testExecuteProcessingThrowsException()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->setMethodsExcept(['execute'])
            ->getMock();
        $processMock->expects($this->once())
            ->method('isProcessing')
            ->willReturn(true);
        $processMock->expects($this->once())
            ->method('start')
            ->willReturnSelf();
        $processMock->expects($this->any())
            ->method('output')
            ->willReturnSelf();
        $processMock->expects($this->once())
            ->method('fail');

        $processMock->execute();
    }

    /**
     * @covers ::execute
     */
    public function testExecuteUnknownHelperMethodThrowsException()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->setMethodsExcept(['execute', '__call'])
            ->getMock();
        $processMock->expects($this->once())
            ->method('isProcessing')
            ->willReturn(false);
        $processMock->expects($this->any())
            ->method('output')
            ->willReturnSelf();

        $processMock->expects($this->once())
            ->method('getHelperInstance')
            ->willReturn($this->anything());
        $processMock->expects($this->once())
            ->method('setData')
            ->with($this->equalTo('status'))
            ->willReturnSelf();
        $processMock->expects($this->once())
            ->method('getData')
            ->with($this->equalTo('method'))
            ->willReturn('foo');
        $processMock->expects($this->once())
            ->method('fail');

        $processMock->execute();
    }
}