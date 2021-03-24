<?php
namespace Mirakl\Test\Unit\Core\Helper\Listing;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group helper
 * @coversDefaultClass \MiraklSeller_Core_Helper_Listing_Process
 */
class ProcessTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Core_Helper_Listing_Process
     */
    protected $_helper;

    protected function setUp()
    {
        $this->_helper = \Mage::helper('mirakl_seller/listing_process');
    }

    protected function tearDown()
    {
        Test::clean();
    }

    /**
     * @covers ::exportOffer
     * @expectedException \Mage_Core_Exception
     * @expectedExceptionMessage This listing is inactive.
     */
    public function testExportOfferWithInactiveListing()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->getMockBuilder(\MiraklSeller_Core_Model_Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();
        $listingMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $listingMock->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $listingMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        self::mockModel('mirakl_seller/listing', $listingMock);

        $this->_helper->exportOffer($processMock, 123);
    }

    /**
     * @covers ::exportProduct
     * @expectedException \Mage_Core_Exception
     * @expectedExceptionMessage This listing is inactive.
     */
    public function testExportProductWithInactiveListing()
    {
        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->getMockBuilder(\MiraklSeller_Core_Model_Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();
        $listingMock->expects($this->once())
            ->method('load')
            ->willReturnSelf();
        $listingMock->expects($this->once())
            ->method('getId')
            ->willReturn(123);
        $listingMock->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        self::mockModel('mirakl_seller/listing', $listingMock);

        $this->_helper->exportProduct($processMock, 123);
    }
}