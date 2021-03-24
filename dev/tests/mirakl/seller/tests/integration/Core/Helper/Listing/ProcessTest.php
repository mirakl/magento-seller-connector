<?php
namespace Mirakl\Test\Integration\Core\Helper\Listing;

use AspectMock\Test;
use Mirakl\Test\Integration\Core\TestCase;
use MiraklSeller_Core_Model_Offer as Offer;

/**
 * @group core
 * @group helper
 * @group listing
 * @group process
 * @coversDefaultClass \MiraklSeller_Core_Helper_Listing_Process
 */
class ListingTest extends TestCase
{
    /**
     * @var \MiraklSeller_Core_Helper_Listing_Process
     */
    protected $_helper;

    /**
     * @var \MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    protected function setUp()
    {
        parent::setUp();
        $this->_helper = \Mage::helper('mirakl_seller/listing_process');
        $this->_offerResource = \Mage::getResourceModel('mirakl_seller/offer');
    }

    /**
     * @covers ::refresh
     */
    public function testRefreshNewListing()
    {
        $listing = $this->_createSampleListing();

        // Mock listing builder in order to specify product ids manually
        $builderMock = new class implements \MiraklSeller_Core_Model_Listing_Builder_Interface {
            public $productIds = [231, 232, 233, 237, 238, 239];

            public function build(\MiraklSeller_Core_Model_Listing $listing) { return $this->productIds; }

            public function prepareForm(\Varien_Data_Form $form, &$data = []) { return $this; }
        };

        $verifier = self::mockListingBuilder($builderMock);

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        // Build and save listing product ids in db
        $this->_helper->refresh($processMock, $listing->getId());

        $verifier->verifyInvokedOnce('getBuilder');

        /**
         * Expected listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        // All offers MUST have the status NEW because not imported yet
        $offers = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_NEW);
        $this->assertSame(6, count($offers));

        // All products MUST have the status NEW because not imported yet
        $products = $this->_offerResource->getListingProductIds($listing->getId(), null, Offer::PRODUCT_NEW);
        $this->assertSame(6, count($products));
    }

    /**
     * @covers ::refresh
     */
    public function testRefreshExistingListing()
    {
        $listing = $this->_createSampleListing();

        // Mock listing builder in order to specify product ids manually
        $builderMock = new class implements \MiraklSeller_Core_Model_Listing_Builder_Interface {
            public $productIds = [231, 232, 233, 237, 238, 239];

            public function build(\MiraklSeller_Core_Model_Listing $listing) { return $this->productIds; }

            public function prepareForm(\Varien_Data_Form $form, &$data = []) { return $this; }
        };

        $verifier = self::mockListingBuilder($builderMock);

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        // Build and save listing product ids in db
        $this->_helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        // Mark 3 offers as SUCCESS and 3 products as ERROR in order to test that they are set to NEW after refresh
        $this->_offerResource->updateOffersStatus($listing->getId(), [231, 232, 233], Offer::OFFER_SUCCESS);
        $this->_offerResource->updateOffersStatus($listing->getId(), [237, 238, 239], Offer::OFFER_ERROR);
        $this->_offerResource->updateProductsStatus($listing->getId(), [231, 232, 233], Offer::PRODUCT_SUCCESS);
        $this->_offerResource->updateProductsStatus($listing->getId(), [237, 238, 239], Offer::PRODUCT_INTEGRATION_ERROR);

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         * | Product Id     | 231     | 232     | 233     | 237               | 238               | 239               |
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         * | Product Status | SUCCESS | SUCCESS | SUCCESS | INTEGRATION_ERROR | INTEGRATION_ERROR | INTEGRATION_ERROR |
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         * | Offer Status   | SUCCESS | SUCCESS | SUCCESS | ERROR             | ERROR             | ERROR             |
         * +----------------+---------+---------+---------+-------------------+-------------------+-------------------+
         */

        $offersNew = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_NEW);
        $this->assertSame(0, count($offersNew));

        $offersSuccess = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_SUCCESS);
        $this->assertSame(3, count($offersSuccess));
        $this->assertSame(['231', '232', '233'], $offersSuccess);

        $offersError = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_ERROR);
        $this->assertSame(3, count($offersError));
        $this->assertSame(['237', '238', '239'], $offersError);

        // Update products in db
        $this->_helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+-----+-----+-----+
         * | Product Id     | 231     | 232     | 233     | 237 | 238 | 239 |
         * +----------------+---------+---------+---------+-----+-----+-----+
         * | Product Status | SUCCESS | SUCCESS | SUCCESS | NEW | NEW | NEW |
         * +----------------+---------+---------+---------+-----+-----+-----+
         * | Offer Status   | SUCCESS | SUCCESS | SUCCESS | NEW | NEW | NEW |
         * +----------------+---------+---------+---------+-----+-----+-----+
         */

        $offersNew = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_NEW);
        $this->assertSame(3, count($offersNew));
        $this->assertSame(['237', '238', '239'], $offersNew);

        $offersSuccess = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_SUCCESS);
        $this->assertSame(3, count($offersSuccess));
        $this->assertSame(['231', '232', '233'], $offersSuccess);

        // Modify listing products manually (keep 3 and remove the 3 others), we should get 3 offers with status DELETE
        $builderMock->productIds = [231, 232, 233];

        // Update products in db
        $this->_helper->refresh($processMock, $listing->getId());

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+--------+--------+--------+
         * | Product Id     | 231     | 232     | 233     | 237    | 238    | 239    |
         * +----------------+---------+---------+---------+--------+--------+--------+
         * | Product Status | SUCCESS | SUCCESS | SUCCESS | NEW    | NEW    | NEW    |
         * +----------------+---------+---------+---------+--------+--------+--------+
         * | Offer Status   | SUCCESS | SUCCESS | SUCCESS | DELETE | DELETE | DELETE |
         * +----------------+---------+---------+---------+--------+--------+--------+
         */

        $offersSuccess = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_SUCCESS);
        $this->assertSame(3, count($offersSuccess));
        $this->assertSame(['231', '232', '233'], $offersSuccess);

        $offersDelete = $this->_offerResource->getListingProductIds($listing->getId(), Offer::OFFER_DELETE);
        $this->assertSame(0, count($offersDelete));

        $verifier->verifyInvokedMultipleTimes('getBuilder', 3);
    }

    /**
     * @covers ::exportOffer
     */
    public function testExportOffer()
    {
        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        $verifier1 = Test::double(\MiraklSeller_Core_Model_Listing::class, [
            'build' => [231, 232, 233, 237, 238, 239]
        ]);

        // Build and save listing product ids in db
        $this->_helper->refresh($processMock, $listing->getId());

        $verifier1->verifyInvokedOnce('build');

        /**
         * Current listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        $this->_offerResource->updateOffersStatus($listing->getId(), [237], Offer::OFFER_PENDING);
        $this->_offerResource->updateOffersStatus($listing->getId(), [231, 233, 238], Offer::OFFER_SUCCESS);
        $this->_offerResource->updateProductsStatus($listing->getId(), [231, 233, 237, 238], Offer::PRODUCT_SUCCESS);
        $this->_offerResource->updateOffersStatus($listing->getId(), [239], Offer::OFFER_DELETE);

        /**
         * Expected listing products:
         * +----------------+---------+-----+---------+---------+---------+--------+
         * | Product Id     | 231     | 232 | 233     | 237     | 238     | 239    |
         * +----------------+---------+-----+---------+---------+---------+--------+
         * | Product Status | SUCCESS | NEW | SUCCESS | SUCCESS | SUCCESS | NEW    |
         * +----------------+---------+-----+---------+---------+---------+--------+
         * | Offer Status   | SUCCESS | NEW | SUCCESS | PENDING | SUCCESS | DELETE |
         * +----------------+---------+-----+---------+---------+---------+--------+
         */

        $cols = ['product_id', 'product_import_status', 'offer_import_status'];
        $offers = $this->_offerResource->getListingProducts($listing->getId(), [], $cols);
        $expectedOffers = [
            231 => [
                'product_id' => '231',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_SUCCESS,
            ],
            232 => [
                'product_id' => '232',
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_status' => Offer::PRODUCT_NEW,
            ],
            233 => [
                'product_id' => '233',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_SUCCESS,
            ],
            237 => [
                'product_id' => '237',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            238 => [
                'product_id' => '238',
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_status' => Offer::OFFER_SUCCESS,
            ],
            239 => [
                'product_id' => '239',
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_status' => Offer::OFFER_DELETE,
            ],
        ];
        $this->assertSame($expectedOffers, $offers);

        $verifier2 = Test::double(\MiraklSeller_Api_Helper_Offer::class, [
            'importOffers' => new \Varien_Object([
                'import_id' => 2028,
            ])
        ]);

        $this->_helper->exportOffer($processMock, $listing->getId());

        $verifier2->verifyInvoked('importOffers');

        $cols = ['product_id', 'product_import_id', 'product_import_status', 'offer_import_id', 'offer_import_status'];
        $offers = $this->_offerResource->getListingProducts($listing->getId(), [], $cols);

        $expectedOffers = [
            231 => [
                'product_id' => '231',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            232 => [
                'product_id' => '232',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            233 => [
                'product_id' => '233',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            237 => [
                'product_id' => '237',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            238 => [
                'product_id' => '238',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => '2028',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
        ];
        $this->assertSame($expectedOffers, $offers);

        // Verify that tracking has been created correctly
        /** @var \MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer_Collection $trackings */
        $trackings = \Mage::getModel('mirakl_seller/listing_tracking_offer')->getCollection();
        $trackings->addListingFilter($listing->getId());

        $this->assertCount(1, $trackings);

        $tracking = $trackings->getFirstItem();
        $this->assertSame('2028', $tracking->getImportId());
        $this->assertNull($tracking->getImportStatus());
    }

    /**
     * @covers ::exportOffer
     */
    public function testExportOfferDelta()
    {
        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->getMockBuilder(\MiraklSeller_Process_Model_Process::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['addOutput', 'output', '__call', 'getData', 'setData'])
            ->getMock();
        $processMock->addOutput('db');

        $verifier1 = Test::double(\MiraklSeller_Core_Model_Listing::class, [
            'build' => [392, 393, 394, 395, 396, 397, 398, 399, 400]
        ]);

        // Build and save listing product ids in db
        $this->_helper->refresh($processMock, $listing->getId());

        $verifier1->verifyInvokedOnce('build');

        /**
         * Current listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 392 | 393 | 394 | 395 | 396 | 397 | 398 | 399 | 400 |
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+-----+-----+-----+
         */

        $verifier2 = Test::double(\MiraklSeller_Api_Helper_Offer::class, [
            'importOffers' => new \Varien_Object([
                'import_id' => 2378,
            ])
        ]);

        $this->_helper->exportOffer($processMock, $listing->getId(), false);
        $verifier2->verifyInvoked('importOffers');

        $cols = ['product_id', 'product_import_id', 'product_import_status', 'offer_import_id', 'offer_import_status'];
        $offers = $this->_offerResource->getListingProducts($listing->getId(), [], $cols);

        /**
         * Expected listing products:
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         * | Product Id     |   392   |   393   |   394   |   395   |   396   |   397   |   398   |   399   |   400   |
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         * | Product Status |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |   NEW   |
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         * | Offer Status   | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING | PENDING |
         * +----------------+---------+---------+---------+---------+---------+---------+---------+---------+---------+
         */

        $expectedOffers = [
            392 => [
                'product_id' => '392',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            393 => [
                'product_id' => '393',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            394 => [
                'product_id' => '394',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            395 => [
                'product_id' => '395',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            396 => [
                'product_id' => '396',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            397 => [
                'product_id' => '397',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            398 => [
                'product_id' => '398',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            399 => [
                'product_id' => '399',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
            400 => [
                'product_id' => '400',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_NEW,
                'offer_import_id' => '2378',
                'offer_import_status' => Offer::OFFER_PENDING,
            ],
        ];
        $this->assertSame($expectedOffers, $offers);

        // Verify that tracking has been created correctly
        /** @var \MiraklSeller_Core_Model_Resource_Listing_Tracking_Offer_Collection $trackings */
        $trackings = \Mage::getModel('mirakl_seller/listing_tracking_offer')->getCollection();
        $trackings->addListingFilter($listing->getId());

        $this->assertCount(1, $trackings);

        $tracking = $trackings->getFirstItem();
        $this->assertSame('2378', $tracking->getImportId());
        $this->assertNull($tracking->getImportStatus());

        // Run export again in delta mode, nothing should change
        $this->_helper->exportOffer($processMock, $listing->getId(), false);
        $this->assertTrue(false !== stripos($processMock->getOutput(), 'No offer to export'));
    }

    /**
     * @covers ::exportProduct
     */
    public function testExportProduct()
    {
        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Process_Model_Process|\PHPUnit_Framework_MockObject_MockObject $processMock */
        $processMock = $this->createMock(\MiraklSeller_Process_Model_Process::class);

        $verifier1 = Test::double(\MiraklSeller_Core_Model_Listing::class, [
            'build' => [231, 232, 233, 237, 238, 239, 240]
        ]);

        // Build and save listing product ids in db
        $this->_helper->refresh($processMock, $listing->getId());

        $verifier1->verifyInvokedOnce('build');

        /**
         * Current listing products:
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Id     | 231 | 232 | 233 | 237 | 238 | 239 |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Product Status | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         * | Offer Status   | NEW | NEW | NEW | NEW | NEW | NEW |
         * +----------------+-----+-----+-----+-----+-----+-----+
         */

        $this->_offerResource->updateProductsStatus($listing->getId(), [232], Offer::PRODUCT_PENDING);
        $this->_offerResource->updateProductsStatus($listing->getId(), [233], Offer::PRODUCT_TRANSFORMATION_ERROR);
        $this->_offerResource->updateProductsStatus($listing->getId(), [237], Offer::PRODUCT_WAITING_INTEGRATION);
        $this->_offerResource->updateProductsStatus($listing->getId(), [238], Offer::PRODUCT_INTEGRATION_COMPLETE);
        $this->_offerResource->updateProductsStatus($listing->getId(), [239], Offer::PRODUCT_INTEGRATION_ERROR);
        $this->_offerResource->updateProductsStatus($listing->getId(), [240], Offer::PRODUCT_SUCCESS);

        /**
         * Expected listing products:
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         * | Product Id     | 231 | 232     | 233           | 237          | 238           | 239        |         |
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         * | Product Status | NEW | PENDING | TRANSF._ERROR | WAITING_INT. | INT._COMPLETE | INT._ERROR | SUCCESS |
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         * | Offer Status   | NEW | NEW     | NEW           | NEW          | NEW           | NEW        | NEW     |
         * +----------------+-----+---------+---------------+--------------+---------------+------------+---------+
         */
        
        $verifier2 = Test::double(\MiraklSeller_Api_Helper_Product::class, [
            'importProducts' => new \Varien_Object([
                'import_id' => 2033
            ])
        ]);

        $this->_helper->exportProduct($processMock, $listing->getId());

        $verifier2->verifyInvoked('importProducts');

        /**
         * Expected listing products:
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Product Id        | 231     | 232     | 233     | 237          | 238           | 239     | 240     |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Product Import Id | 2033    | NULL    | 2033    | NULL         | NULL          | 2033    | NULL    |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Product Status    | PENDING | PENDING | PENDING | WAITING_INT. | INT._COMPLETE | PENDING | SUCCESS |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         * | Offer Status      | NEW     | NEW     | NEW     | NEW          | NEW           | NEW     | NEW     |
         * +-------------------+---------+---------+---------+--------------+---------------+---------+---------+
         */

        $cols = ['product_id', 'product_import_id', 'product_import_status', 'offer_import_id', 'offer_import_status'];
        $products = $this->_offerResource->getListingProducts($listing->getId(), [], $cols);
        $expectedProducts = [
            231 => [
                'product_id' => '231',
                'product_import_id' => '2033',
                'product_import_status' => Offer::PRODUCT_PENDING,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            232 => [
                'product_id' => '232',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_PENDING,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            233 => [
                'product_id' => '233',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_TRANSFORMATION_ERROR,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            237 => [
                'product_id' => '237',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_WAITING_INTEGRATION,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            238 => [
                'product_id' => '238',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_INTEGRATION_COMPLETE,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            239 => [
                'product_id' => '239',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_INTEGRATION_ERROR,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
            240 => [
                'product_id' => '240',
                'product_import_id' => null,
                'product_import_status' => Offer::PRODUCT_SUCCESS,
                'offer_import_id' => null,
                'offer_import_status' => Offer::OFFER_NEW,
            ],
        ];
        $this->assertSame($expectedProducts, $products);

        // Verify that tracking has been created correctly
        /** @var \MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection $trackings */
        $trackings = \Mage::getModel('mirakl_seller/listing_tracking_product')->getCollection();
        $trackings->addListingFilter($listing->getId());

        $this->assertCount(1, $trackings);

        $tracking = $trackings->getFirstItem();
        $this->assertSame('2033', $tracking->getImportId());
        $this->assertNull($tracking->getImportStatus());
    }
}