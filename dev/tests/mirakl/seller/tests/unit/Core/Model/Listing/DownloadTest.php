<?php
namespace Mirakl\Test\Unit\Core\Model\Listing;

use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing_Download
 */
class DownloadTest extends TestCase
{
    /**
     * @covers ::prepare
     */
    public function testPrepare()
    {
        $expectedResult = 'name;description';

        $adapterMock = $this->createMock(\MiraklSeller_Core_Model_Listing_Download_Adapter_Interface::class);
        $adapterMock->expects($this->once())
            ->method('write')
            ->willReturn(123);
        $adapterMock->expects($this->once())
            ->method('getContents')
            ->willReturn($expectedResult);

        $exportModelMock = $this->createMock(\MiraklSeller_Core_Model_Listing_Export_Interface::class);
        $exportModelMock->expects($this->once())
            ->method('export')
            ->willReturn([['name', 'description']]);

        /** @var \MiraklSeller_Core_Model_Listing $listingMock */
        $listingMock = $this->createMock(\MiraklSeller_Core_Model_Listing::class);

        /** @var \MiraklSeller_Core_Model_Listing_Download $downloadModel */
        $downloadModel = \Mage::getModel('mirakl_seller/listing_download', [
            'adapter' => $adapterMock,
            'export_model' => $exportModelMock
        ]);

        $this->assertSame($expectedResult, $downloadModel->prepare($listingMock));
    }

    /**
     * @covers ::prepare
     */
    public function testPrepareWithEmptyProducts()
    {
        $expectedResult = '';

        $exportModelMock = $this->createMock(\MiraklSeller_Core_Model_Listing_Export_Interface::class);
        $exportModelMock->expects($this->once())
            ->method('export')
            ->willReturn([]);

        /** @var \MiraklSeller_Core_Model_Listing $listingMock */
        $listingMock = $this->createMock(\MiraklSeller_Core_Model_Listing::class);

        /** @var \MiraklSeller_Core_Model_Listing_Download $downloadModel */
        $downloadModel = \Mage::getModel('mirakl_seller/listing_download', [
            'export_model' => $exportModelMock
        ]);

        $this->assertSame($expectedResult, $downloadModel->prepare($listingMock));
    }

    /**
     * @covers ::getFileExtension
     */
    public function testGetFileExtension()
    {
        $expectedResult = 'csv';

        $adapterMock = $this->createMock(\MiraklSeller_Core_Model_Listing_Download_Adapter_Interface::class);
        $adapterMock->expects($this->once())
            ->method('getFileExtension')
            ->willReturn($expectedResult);

        /** @var \MiraklSeller_Core_Model_Listing_Download $downloadModel */
        $downloadModel = \Mage::getModel('mirakl_seller/listing_download', [
            'adapter' => $adapterMock
        ]);

        $this->assertSame($expectedResult, $downloadModel->getFileExtension());
    }
}