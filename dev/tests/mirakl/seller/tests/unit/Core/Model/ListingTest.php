<?php
namespace Mirakl\Test\Unit\Core\Model\Listing;

use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing
 */
class ListingTest extends TestCase
{
    /**
     * @covers ::getBuilder
     * @expectedException \Mage_Core_Exception
     * @expectedExceptionMessage Listing builder must implement MiraklSeller_Core_Model_Listing_Builder_Interface
     */
    public function testGetBuilder()
    {
        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->getMockBuilder(\MiraklSeller_Core_Model_Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getBuilder'])
            ->getMock();
        $listingMock->expects($this->once())
            ->method('getBuilderModel')
            ->willReturn(\stdClass::class);

        $listingMock->getBuilder();
    }
}