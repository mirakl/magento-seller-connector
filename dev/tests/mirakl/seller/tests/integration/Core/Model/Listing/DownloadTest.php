<?php
namespace Mirakl\Test\Integration\Core\Model\Listing;

use Mirakl\Aspect\AspectMockTrait;
use Mirakl\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing_Download
 */
class DownloadTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @covers ::prepare
     * @param   array   $productIds
     * @param   string  $expectedResult
     * @dataProvider getTestPrepareDataProvider
     */
    public function testPrepare($productIds, $expectedResult)
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

        /** @var \MiraklSeller_Core_Model_Listing_Download $downloadModel */
        $downloadModel = \Mage::getModel('mirakl_seller/listing_download');
        $result = $downloadModel->prepare($listingMock);

        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestPrepareDataProvider()
    {
        return [
            [[231, 232, 233], $this->_getFileContents('expected_download_result_1.csv')],
            [[392, 393, 394, 395, 396, 397, 398, 399], $this->_getFileContents('expected_download_result_2.csv')],
            [[], ''],
        ];
    }

    /**
     * @covers ::getFileExtension
     */
    public function testGetFileExtension()
    {
        /** @var \MiraklSeller_Core_Model_Listing_Download $downloadModel */
        $downloadModel = \Mage::getModel('mirakl_seller/listing_download');

        $this->assertSame('csv', $downloadModel->getFileExtension());
    }

    /**
     * @covers ::getFileExtension
     */
    public function testGetFileExtensionWithCustomAdapter()
    {
        $customAdapter = new class() implements \MiraklSeller_Core_Model_Listing_Download_Adapter_Interface {
            public function getContents() {}

            public function getFileExtension() { return 'xml'; }

            public function write(array $data) {}
        };

        /** @var \MiraklSeller_Core_Model_Listing_Download $downloadModel */
        $downloadModel = \Mage::getModel('mirakl_seller/listing_download', [
            'adapter' => $customAdapter
        ]);

        $this->assertSame('xml', $downloadModel->getFileExtension());
    }
}