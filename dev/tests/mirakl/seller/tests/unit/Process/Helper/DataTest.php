<?php
namespace Mirakl\Test\Unit\Process\Helper;

use AspectMock\Test;
use PHPUnit\Framework\TestCase;

/**
 * @group process
 * @group helper
 * @coversDefaultClass \MiraklSeller_Process_Helper_Data
 */
class ProcessTest extends TestCase
{
    /**
     * @var \MiraklSeller_Process_Helper_Data
     */
    protected $_helper;

    protected function setUp()
    {
        $this->_helper = \Mage::helper('mirakl_seller_process');
    }

    protected function tearDown()
    {
        Test::clean();
    }

    /**
     * @covers ::getPendingProcess
     */
    public function testGetPendingProcess()
    {
        /** @var \MiraklSeller_Process_Model_Resource_Process_Collection|\PHPUnit_Framework_MockObject_MockObject $collectionMock */
        $collectionMock = $this->createMock(\MiraklSeller_Process_Model_Resource_Process_Collection::class);
        $collectionMock->expects($this->exactly(2))
            ->method('addProcessingFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(2))
            ->method('addPendingFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(2))
            ->method('getColumnValues')
            ->willReturn([]);
        $collectionMock->expects($this->exactly(2))
            ->method('addExcludeHashFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(2))
            ->method('addParentCompletedFilter')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(2))
            ->method('setOrder')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(2))
            ->method('count')
            ->willReturnOnConsecutiveCalls(0, 3);
        $collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($this->createMock(\MiraklSeller_Process_Model_Process::class));

        $selectMock = $this->createMock(\Varien_Db_Select::class);
        $selectMock->expects($this->exactly(2))
            ->method('limit')
            ->willReturnSelf();
        $collectionMock->expects($this->exactly(2))
            ->method('getSelect')
            ->willReturn($selectMock);
        
        $verifier = Test::double(\MiraklSeller_Process_Model_Process::class, ['getCollection' => $collectionMock]);

        $this->assertNull($this->_helper->getPendingProcess());
        $this->assertInstanceOf(\MiraklSeller_Process_Model_Process::class, $this->_helper->getPendingProcess());

        $verifier->verifyInvokedMultipleTimes('getCollection', 4);
    }
}