<?php
namespace Mirakl\Test\Integration\Core\Model\Listing\Export;

use Mirakl\Aspect\AspectMockTrait;
use Mirakl\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing_Export_Products
 */
class ProductsTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportDataProvider
     */
    public function testExport($productIds, $expectedResult)
    {
        self::mockBaseUrl();
        self::mockConfigValue('mirakl_seller/listing/nb_image_exported', 1);

        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(\MiraklSeller_Core_Model_Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(\MiraklSeller_Api_Model_Connection::class));

        /** @var \MiraklSeller_Core_Model_Listing_Export_Products $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_products');

        $result = $exportModel->export($listingMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_products_1.json')],
            [[340, 341, 342, 343, 344], $this->_getJsonFileContents('expected_export_products_2.json')],
            [[547, 548, 549, 551, 552, 553, 554], $this->_getJsonFileContents('expected_export_products_3.json')],
            [[], []],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $variantsAttributes
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithVariantsAttributesDataProvider
     */
    public function testExportWithVariantsAttributes($productIds, $variantsAttributes, $expectedResult)
    {
        self::mockBaseUrl();
        self::mockConfigValue('mirakl_seller/listing/nb_image_exported', 1);

        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(\MiraklSeller_Core_Model_Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(\MiraklSeller_Api_Model_Connection::class));

        $listingMock->expects($this->any())
            ->method('getVariantsAttributes')
            ->willReturn($variantsAttributes);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Products $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_products');

        $result = $exportModel->export($listingMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithVariantsAttributesDataProvider()
    {
        return [
            [[267, 268, 269], ['color'], $this->_getJsonFileContents('expected_export_products_with_variants_attributes_1.json')],
            [[267, 268, 269], ['color', 'size'], $this->_getJsonFileContents('expected_export_products_with_variants_attributes_2.json')],
            [[267, 268, 269], ['name'], $this->_getJsonFileContents('expected_export_products_1.json')],
            [[267, 268, 269], ['shoe_size'], $this->_getJsonFileContents('expected_export_products_1.json')],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $exportableAttributes
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithExportableAttributesDataProvider
     */
    public function testExportWithExportableAttributes($productIds, $exportableAttributes, $expectedResult)
    {
        self::mockBaseUrl();
        self::mockConfigValue('mirakl_seller/listing/nb_image_exported', 1);

        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->createMock(\MiraklSeller_Core_Model_Listing::class);
        $listingMock->expects($this->any())
            ->method('getProductIds')
            ->willReturn($productIds);

        $connection = $this->createMock(\MiraklSeller_Api_Model_Connection::class);
        $connection->expects($this->any())
            ->method('getExportableAttributes')
            ->willReturn($exportableAttributes);

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Products $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_products');

        $result = $exportModel->export($listingMock);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithExportableAttributesDataProvider()
    {
        return [
            [[267, 268, 269], [], $this->_getJsonFileContents('expected_export_products_1.json')],
            [[267, 268, 269], ['description', 'short_description'], $this->_getJsonFileContents('expected_export_products_with_exportable_attributes_1.json')],
        ];
    }
}