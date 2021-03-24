<?php
namespace Mirakl\Test\Integration\Core\Model\Listing\Export;

use AspectMock\Test;
use Mirakl\Test\Integration\Core\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing_Export_Offers
 */
class OffersTest extends TestCase
{
    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportDataProvider
     */
    public function testExport($productIds, $expectedResult)
    {
        $verifier = Test::double(\MiraklSeller_Core_Helper_Config::class, ['getOfferFieldsMapping' => []]);

        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Core_Model_Offer_Loader $offerLoader */
        $offerLoader = \Mage::getModel('mirakl_seller/offer_loader');
        $offerLoader->load($listing->getId(), $productIds);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Offers $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_offers');

        $result = $exportModel->export($listing);

        $verifier->verifyInvoked('getOfferFieldsMapping');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_offers_1.json')],
            [[340, 341, 342, 343, 344], $this->_getJsonFileContents('expected_export_offers_2.json')],
            [[285, 286, 287, 512, 513], $this->_getJsonFileContents('expected_export_offers_with_prices_1.json')],
            [[], []],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithCustomPriceFieldDataProvider
     */
    public function testExportWithCustomPriceField($productIds, $expectedResult)
    {
        $listing = $this->_createSampleListing();

        $listing->getConnection()->setExportedPricesAttribute('gift_wrapping_price');

        /** @var \MiraklSeller_Core_Model_Offer_Loader $offerLoader */
        $offerLoader = \Mage::getModel('mirakl_seller/offer_loader');
        $offerLoader->load($listing->getId(), $productIds);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Offers $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_offers');

        $result = $exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithCustomPriceFieldDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_offers_with_custom_price_1.json')],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithPromotionActivatedDataProvider
     */
    public function testExportWithPromotionActivated($productIds, $expectedResult)
    {
        $verifier = Test::double(\MiraklSeller_Core_Helper_Config::class, [
            'isPromotionPriceExported' => true,
            'getOfferFieldsMapping' => [],
        ]);

        /** @var \MiraklSeller_Core_Helper_Config $config */
        $config = \Mage::helper('mirakl_seller/config');
        $this->assertTrue($config->isPromotionPriceExported());

        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Core_Model_Offer_Loader $offerLoader */
        $offerLoader = \Mage::getModel('mirakl_seller/offer_loader');
        $offerLoader->load($listing->getId(), $productIds);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Offers $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_offers');

        $result = $exportModel->export($listing);

        $verifier->verifyInvoked('isPromotionPriceExported');
        $verifier->verifyInvoked('getOfferFieldsMapping');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithPromotionActivatedDataProvider()
    {
        return [
            [[267, 268, 269], $this->_getJsonFileContents('expected_export_offers_1.json')],
            [[340, 341, 342, 343, 344], $this->_getJsonFileContents('expected_export_offers_2.json')],
            [[], []],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $additionalFields
     * @param   array   $additionalFieldsValues
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithAdditionalFieldsDataProvider
     */
    public function testExportWithAdditionalFields(
        $productIds, $additionalFields, $additionalFieldsValues, $expectedResult
    ) {

        $listing = $this->_createSampleListing();

        $verifier1 = Test::double(\MiraklSeller_Core_Helper_Config::class, ['getOfferFieldsMapping' => []]);
        $verifier2 = Test::double($listing, [
            'getOfferAdditionalFields' => $additionalFields,
            'getOfferAdditionalFieldsValues' => $additionalFieldsValues
        ]);

        /** @var \MiraklSeller_Core_Model_Offer_Loader $offerLoader */
        $offerLoader = \Mage::getModel('mirakl_seller/offer_loader');
        $offerLoader->load($listing->getId(), $productIds);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Offers $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_offers');

        $result = $exportModel->export($listing);

        $verifier1->verifyInvoked('getOfferFieldsMapping');
        $verifier2->verifyInvoked('getOfferAdditionalFields');
        $verifier2->verifyInvoked('getOfferAdditionalFieldsValues');

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithAdditionalFieldsDataProvider()
    {
        return [
            [
                [267, 268, 269],
                $this->_getJsonFileContents('sample_additional_fields.json'),
                $this->_getJsonFileContents('sample_additional_fields_values.json'),
                $this->_getJsonFileContents('expected_export_offers_with_additional_fields_1.json'),
            ],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithOrderConditionConfigurationDataProvider
     */
    public function testExportWithOrderConditionConfiguration($productIds, $expectedResult)
    {
        Test::double(\MiraklSeller_Core_Helper_Config::class, ['getOfferFieldsMapping' => []]);
        Test::double(\MiraklSeller_Core_Helper_Inventory::class, [
             '_getConfigMinSaleQuantity' => '2',
             '_getConfigEnableQtyIncrements' => true,
             '_getConfigQtyIncrements' => '2',
         ]);

        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Core_Model_Offer_Loader $offerLoader */
        $offerLoader = \Mage::getModel('mirakl_seller/offer_loader');
        $offerLoader->load($listing->getId(), $productIds);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Offers $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_offers');

        $result = $exportModel->export($listing);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithOrderConditionConfigurationDataProvider()
    {
        return [
            [
                [267, 268, 269, 340],
                $this->_getJsonFileContents('expected_export_offers_with_order_condition_configuration_1.json')
            ],
        ];
    }

    /**
     * @covers ::export
     * @param   array   $productIds
     * @param   array   $expectedResult
     * @dataProvider getTestExportWithDeleteOffersDataProvider
     */
    public function testExportWithDeleteOffers($productIds, $expectedResult)
    {
        $listing = $this->_createSampleListing();

        /** @var \MiraklSeller_Core_Model_Resource_Offer $offerResource */
        $offerResource = \Mage::getResourceModel('mirakl_seller/offer');
        $offerResource->createOffers($listing->getId(), $productIds);
        $offerResource->updateProducts($listing->getId(), $productIds, [
            'offer_import_status' => \MiraklSeller_Core_Model_Offer::OFFER_DELETE,
        ]);

        /** @var \MiraklSeller_Core_Model_Listing_Export_Offers $exportModel */
        $exportModel = \Mage::getModel('mirakl_seller/listing_export_offers');

        $result = $exportModel->export($listing);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return  array
     */
    public function getTestExportWithDeleteOffersDataProvider()
    {
        return [
            [
                [267, 268],
                $this->_getJsonFileContents('expected_export_offers_delete_1.json')
            ],
        ];
    }
}