<?php
namespace Mirakl\Test\Integration\Core\Helper\Tracking;

use AspectMock\Test;

use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MCI\Common\Domain\Product\ProductImportResult;
use Mirakl\Test\Integration\Core\TestCase;

use MiraklSeller_Core_Model_Offer as Offer;

/**
 * @group core
 * @group helper
 * @group tracking
 * @group process
 * @coversDefaultClass \MiraklSeller_Core_Helper_Tracking_Process
 */
class ProcessTest extends TestCase
{
    /**
     * @var \MiraklSeller_Core_Helper_Tracking_Process
     */
    protected $_helper;

    /**
     * @var \MiraklSeller_Core_Model_Offer
     */
    protected $_offerModel;

    protected function setUp()
    {
        parent::setUp();
        $this->_helper = \Mage::helper('mirakl_seller/tracking_process');
        $this->_offerModel = \Mage::getModel('mirakl_seller/offer');
    }

    /**
     * @covers ::updateOffersImportStatus
     * @param   string  $jsonFile
     * @dataProvider getUpdateProductsImportStatusProvider
     */
    public function testUpdateProductsImportStatus($jsonFile)
    {
        $listing = $this->_createSampleListing();

        $connection = $listing->getConnection();
        $connection->setSkuCode('shop_sku');
        $connection->setErrorsCode('error');
        $connection->setSuccessSkuCode('shop_sku');
        $connection->setMessagesCode('error');
        $connection->save();

        $data = $this->_getJsonFileContents($jsonFile);

        foreach ($data['offers_before'] as $offerData) {
            $offer = \Mage::getModel('mirakl_seller/offer');
            $offer->setData($offerData);
            $offer->setListingId($listing->getId());
            $offer->save();
        }

        $trackingProduct = \Mage::getModel('mirakl_seller/listing_tracking_product');
        $trackingProduct->setData($data['listing_tracking_product']);
        $trackingProduct->setListingId($listing->getId());
        $trackingProduct->save();

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        Test::double(\MiraklSeller_Api_Helper_Product::class, [
            'getProductImportResult' => new ProductImportResult($data['P42']['product_import_trackings'][0]),
            'getProductsTransformationErrorReport' => new FileWrapper(implode("\n", $data['P47'])),
            'getProductsIntegrationErrorReport' => new FileWrapper(implode("\n", $data['P44'])),
            'getNewProductsIntegrationReport' => new FileWrapper(implode("\n", $data['P45']))
        ]);

        $this->_helper->updateProductsImportStatus($processMock, $trackingProduct->getId());

        $trackingProduct->load($trackingProduct->getId());

        $this->assertEquals($data['listing_tracking_product_status_after'], $trackingProduct->getImportStatus());
        $this->assertEquals($data['listing_tracking_product_status_after_reason'], $trackingProduct->getImportStatusReason());

        $offers = $this->_offerModel->getCollection()->addFilter('listing_id', $listing->getId());
        $this->assertCount(count($data['offers_after']), $offers);

        foreach ($offers as $offer) {
            $this->assertArrayHasKey($offer->getProductId(), $data['offers_after']);
            foreach ($data['offers_after'][$offer->getProductId()] as $key => $value) {
                $this->assertEquals($value, $offer->getData($key));
            }
        }
    }

    /**
     * @return  array
     */
    public function getUpdateProductsImportStatusProvider()
    {
        return [
            ['transformationSuccess.json'],
            ['transformationFail.json'],
            ['transformationMixed.json'],
            ['integrationSuccessWithoutFile.json'],
            ['integrationSuccessWithFile.json'],
            ['integrationErrorWithoutFile.json'],
            ['integrationErrorWithFile.json'],
            ['integrationMixedWithoutFile.json'],
            ['integrationMixedWithFile.json'],
            ['integrationSuccessWithFileAndTransfoDone.json'],
        ];
    }
}