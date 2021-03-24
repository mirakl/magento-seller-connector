<?php
namespace Mirakl\Test\Unit\Core\Model\Listing\Export\Formatter;

use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing_Export_Formatter_Product
 */
class ProductTest extends TestCase
{
    /**
     * @var \MiraklSeller_Core_Model_Listing_Export_Formatter_Product
     */
    protected $_formatter;

    protected function setUp()
    {
        $this->_formatter = \Mage::getModel('mirakl_seller/listing_export_formatter_product');
    }

    /**
     * @covers ::format
     */
    public function testFormat()
    {
        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->getMockBuilder(\MiraklSeller_Core_Model_Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['validate'])
            ->getMock();

        $connection = $this->createMock(\MiraklSeller_Api_Model_Connection::class);
        $connection->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn([]);
        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $expectedKeys = [
            'image_1',
            'image_2',
            'image_3',
            'image_4',
            'image_5',
            'category',
            'variant_group_code',
        ];

        $data = [
            'sku'         => 'ABCDEF-123',
            'description' => 'Lorem ipsum dolor sit amet',
            'color'       => 'Blue',
            'size'        => 'XL',
        ];

        $this->assertSame($expectedKeys, array_keys($this->_formatter->format($data, $listingMock)));
    }
}