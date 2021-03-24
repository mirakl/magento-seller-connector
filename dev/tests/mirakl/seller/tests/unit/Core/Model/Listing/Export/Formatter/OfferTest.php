<?php
namespace Mirakl\Test\Unit\Core\Model\Listing\Export\Formatter;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group model
 * @group listing
 * @group export
 * @coversDefaultClass \MiraklSeller_Core_Model_Listing_Export_Formatter_Offer
 */
class OfferTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Core_Model_Listing_Export_Formatter_Offer
     */
    protected $_formatter;

    /**
     * @var \MiraklSeller_Core_Helper_Config
     */
    protected $_config;

    protected function setUp()
    {
        $this->_formatter = \Mage::getModel('mirakl_seller/listing_export_formatter_offer');
        $this->_config = \Mage::helper('mirakl_seller/config');
    }

    protected function tearDown()
    {
        Test::clean();
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

        /** @var \MiraklSeller_Api_Model_Connection|\PHPUnit_Framework_MockObject_MockObject $connectionMock */
        $connectionMock = $this->getMockBuilder(\MiraklSeller_Api_Model_Connection::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept()
            ->getMock();

        $listingMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $expectedKeys = [
            'sku',
            'product-id',
            'product-id-type',
            'description',
            'internal-description',
            'price',
            'price-additional-info',
            'quantity',
            'min-quantity-alert',
            'state',
            'available-start-date',
            'available-end-date',
            'logistic-class',
            'favorite-rank',
            'discount-price',
            'discount-start-date',
            'discount-end-date',
            'discount-ranges',
            'min-order-quantity',
            'max-order-quantity',
            'package-quantity',
            'leadtime-to-ship',
            'allow-quote-requests',
            'update-delete',
            'price-ranges',
            'product-tax-code',
            'entity_id',
        ];

        $data = [
            'sku'           => 'ABCDEF-123',
            'description'   => 'Lorem ipsum dolor sit amet',
            'price'         => 259.21,
            'final_price'   => 259.21,
            'special_price' => null,
            'qty'           => 12,
            'tier_prices'   => '',
            'entity_id'     => 1,
        ];

        $this->assertSame($expectedKeys, array_keys($this->_formatter->format($data, $listingMock)));
    }

    /**
     * @covers ::computePromotion
     * @param   float   $basePrice
     * @param   float   $finalPrice
     * @param   float   $specialPrice
     * @param   string  $specialFromDate
     * @param   string  $specialToDate
     * @param   bool    $isPromoPriceExported
     * @param   array   $expected
     * @dataProvider getComputePromotionDataProvider
     */
    public function testComputePromotion(
        $basePrice, $finalPrice, $specialPrice, $specialFromDate, $specialToDate, $isPromoPriceExported, $expected
    ) {
        self::mockConfigValue(
            \MiraklSeller_Core_Helper_Config::XML_PATH_DISCOUNT_ENABLE_PROMOTION_CATALOG_PRICE_RULE,
            $isPromoPriceExported
        );

        $this->assertSame($this->_config->isPromotionPriceExported(), $isPromoPriceExported);

        $computedPromotion = $this->_formatter->computePromotion(
            $basePrice, $finalPrice, $specialPrice, $specialFromDate, $specialToDate
        );
        $this->assertSame($expected, $computedPromotion);
    }

    /**
     * @return  array
     */
    public function getComputePromotionDataProvider()
    {
        return [
            [99, 99, 0, '', '', true, [ // No promotion rule, no special price
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [99, 99, 99, '', '', true, [ // No promotion rule, special price = base price, ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [99, 79, 0, '', '', true, [ // Promotion rule applied, no special price
                'discount_price'      => '79.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [99, 79, 0, '2012-01-01', '2999-12-31', true, [ // Promotion rule applied, no special price, must ignore date range
                'discount_price'      => '79.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 149.5, 149.5, '', '', true, [ // Promotion rule applied or valid special price applied
                'discount_price'      => '149.50',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 120, 150, '', '', true, [ // Promotion rule applied because lower than special price
                'discount_price'      => '120.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 120, 150, '2017-01-01', '', true, [ // Promotion rule applied because lower than special price, ignore date range
                'discount_price'      => '120.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [190, 120, 120, '2017-01-01', '', true, [ // Promotion rule maybe applied and equals to special price, must fill start date and discount price
                'discount_price'      => '120.00',
                'discount_start_date' => '2017-01-01',
                'discount_end_date'   => '',
            ]],
            [190, 120, 120, '', '2999-12-31', true, [ // Special price applied with valid end date, must fill end date
                'discount_price'      => '120.00',
                'discount_start_date' => '',
                'discount_end_date'   => '2999-12-31',
            ]],
            [49, 49, 29, '', '2012-12-31', true, [ // No promotion rule, invalid special price date, must ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [29, 19.9, 19.9, '2012-08-31', '2123-12-31', true, [ // Special price applied with valid date range, fill all fields
                'discount_price'      => '19.90',
                'discount_start_date' => '2012-08-31',
                'discount_end_date'   => '2123-12-31',
            ]],
            [49, 19, 29, '', '', false, [ // There is a promotion price but not allowed in config, use special price
                'discount_price'      => '29.00',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [49, 19, 29, '2012-08-31', '2123-12-31', false, [ // There is a promotion price but not allowed in config, use special price with date ranges
                'discount_price'      => '29.00',
                'discount_start_date' => '2012-08-31',
                'discount_end_date'   => '2123-12-31',
            ]],
            [49, 49, 49, '2012-08-31', '2123-12-31', false, [ // Valid special price date ranges but invalid special price, must ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [49, 19, 49, '2012-08-31', '', false, [ // Valid promotion price but not allowed in config and valid special price date ranges but invalid special price, must ignore all fields
                'discount_price'      => '',
                'discount_start_date' => '',
                'discount_end_date'   => '',
            ]],
            [224.9, 199, 199, '2017-01-01', '', false, [ // Promotion rule maybe applied but not allowed in config but equals to special price, must fill start date and discount price
                'discount_price'      => '199.00',
                'discount_start_date' => '2017-01-01',
                'discount_end_date'   => '',
            ]],
        ];
    }
}