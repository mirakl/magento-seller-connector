<?php
namespace Mirakl\Test\Integration\Core\Model\Resource;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use Mirakl\Test\Integration\Core\TestCase;
use MiraklSeller_Core_Model_Offer as Offer;

/**
 * @group core
 * @group model
 * @group resource
 * @group offer
 * @coversDefaultClass \MiraklSeller_Core_Model_Resource_Offer
 */
class CollectionTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Core_Helper_Listing_Process
     */
    protected $_listingHelper;

    /**
     * @var \MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    protected function setUp()
    {
        $this->_listingHelper = \Mage::helper('mirakl_seller/listing_process');
        $this->_offerResource = \Mage::getResourceModel('mirakl_seller/offer');
    }

    /**
     * @covers ::getListingFailedProductIds
     * @param   string  $trackingUpdatedDate
     * @param   string  $currentDate
     * @param   int     $delay
     * @param   int     $expectedProductsCount
     * @dataProvider getTestGetListingFailedProductIdsDataProvider
     */
    public function testGetListingFailedProductIds($trackingUpdatedDate, $currentDate, $delay, $expectedProductsCount)
    {
        $verifier = Test::double(\Varien_Date::class, [
            'now' => $trackingUpdatedDate,
        ]);

        $tracking = $this->_createSampleProductTracking();

        $verifier->verifyInvoked('now');
        Test::clean(\Varien_Date::class);

        $listing = $tracking->getListing();

        // Mock listing builder in order to specify product ids manually
        $builderMock = new class implements \MiraklSeller_Core_Model_Listing_Builder_Interface {
            public $productIds = [547, 548, 549, 551, 552, 553, 554]; // Attribute set = Jewelry

            public function build(\MiraklSeller_Core_Model_Listing $listing) { return $this->productIds; }

            public function prepareForm(\Varien_Data_Form $form, &$data = []) { return $this; }
        };

        $verifier = self::mockListingBuilder($builderMock);

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        // Build and save listing product ids in db
        $this->_listingHelper->refresh($processMock, $listing->getId());

        $verifier->verifyInvokedOnce('getBuilder');

        // Verify that offers have been saved correctly
        $offers = $this->_offerResource->getListingProductIds($listing->getId());
        $this->assertSame(7, count($offers));

        // Define some products as failed manually
        $this->_offerResource->updateProducts($listing->getId(), [549, 551], [
            'product_import_id'     => $tracking->getImportId(),
            'product_import_status' => Offer::PRODUCT_TRANSFORMATION_ERROR,
        ]);

        $verifier = Test::double(\Varien_Date::class, [
            'now' => $currentDate,
        ]);

        $failedProductIds = $this->_offerResource->getListingFailedProductIds($listing->getId(), $delay);
        $this->assertCount($expectedProductsCount, $failedProductIds);

        $verifier->verifyInvokedOnce('now');
    }

    /**
     * @return  array
     */
    public function getTestGetListingFailedProductIdsDataProvider()
    {
        return [
            ['2017-10-12 14:35:49', '2017-12-27 16:55:07', 10, 2],
            ['2017-12-25 09:14:30', '2017-12-27 16:55:07', 5, 0],
            ['2017-12-25 09:14:30', '2017-12-27 16:55:07', 1, 2],
            ['2017-12-27 16:55:07', '2017-12-25 09:14:30', 100, 0],
        ];
    }
}