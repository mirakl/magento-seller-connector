<?php
namespace Mirakl\Test\Integration\Core\Model\Resource\Product;

use Mirakl\Aspect\AspectMockTrait;
use Mirakl\Test\Integration\TestCase;

/**
 * @group core
 * @group model
 * @group resource
 * @group collection
 * @coversDefaultClass \MiraklSeller_Core_Model_Resource_Product_Collection
 */
class CollectionTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Core_Model_Resource_Product_Collection
     */
    protected $_collection;

    /**
     * @var \Mage_Catalog_Model_Resource_Product
     */
    protected $_productResource;

    protected function setUp()
    {
        $this->_collection = \Mage::getResourceModel('mirakl_seller/product_collection');
        $this->_productResource = \Mage::getResourceModel('catalog/product');
    }

    /**
     * @covers ::addAttributeOptionValue
     * @param   array   $productIds
     * @param   array   $attributeCodes
     * @param   array   $expectedItems
     * @dataProvider getTestAddAttributeOptionValueDataProvider
     */
    public function testAddAttributeOptionValue($productIds, $attributeCodes, $expectedItems)
    {
        $this->_collection->addIdFilter($productIds);

        foreach ($attributeCodes as $attrCode) {
            $this->_collection->addAttributeOptionValue($this->_getAttribute($attrCode));
        }

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddAttributeOptionValueDataProvider()
    {
        return [
            [
                [539],
                ['color'],
                [
                    539 => ['entity_id' => '539', 'color' => 'Purple'],
                ]
            ],
            [
                [881],
                ['color', 'size'],
                [
                    881 => ['entity_id' => '881', 'color' => 'Black', 'size' => 'S'],
                ]
            ],
            [
                [310, 394],
                ['color', 'size', 'electronic_type'],
                [
                    310 => ['entity_id' => '310', 'color' => 'Blue', 'size' => '2', 'electronic_type' => null],
                    394 => ['entity_id' => '394', 'color' => 'Black', 'size' => null, 'electronic_type' => 'Accessories'],
                ]
            ],
        ];
    }

    /**
     * @covers ::addListingPriceData
     * @param   array   $productIds
     * @param   int     $storeId
     * @param   array   $expectedItems
     * @dataProvider getTestAddListingPriceDataDataProvider
     */
    public function testAddListingPriceData($productIds, $storeId, $expectedItems)
    {
        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->getMockBuilder(\MiraklSeller_Core_Model_Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['__call', 'getData', 'getWebsiteId'])
            ->getMock();

        $listingMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        $this->_collection
            ->addListingPriceData($listingMock)
            ->addIdFilter($productIds);

        $this->assertSame($storeId, $this->_collection->getStoreId());
        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddListingPriceDataDataProvider()
    {
        return [
            [
                337, 0, [
                    337 => [
                        'entity_id' => '337', 'price' => '295.0000', 'tax_class_id' => '2', 'final_price' => '295.0000',
                        'minimal_price' => '295.0000', 'min_price' => '295.0000', 'max_price' => '295.0000',
                        'tier_price' => null
                    ],
                ],
            ],
            [
                384, 1, [
                    384 => [
                        'entity_id' => '384', 'price' => '240.0000', 'tax_class_id' => '2', 'final_price' => '120.0000',
                        'minimal_price' => '120.0000', 'min_price' => '120.0000', 'max_price' => '120.0000',
                        'tier_price' => null
                    ],
                ],
            ],
            [
                123456789, 2, []
            ],
        ];
    }

    /**
     * @covers ::addQuantityToSelect
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddQuantityToSelectDataProvider
     */
    public function testAddQuantityToSelect($productIds, $expectedItems)
    {
        $this->_collection
            ->addQuantityToSelect()
            ->addIdFilter($productIds);

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddQuantityToSelectDataProvider()
    {
        return [
            [551, [551 => [
                'entity_id' => '551', 'qty' => '15.0000', 'use_config_min_sale_qty' => '1',
                'min_sale_qty' => '1.0000', 'use_config_max_sale_qty' => '1', 'max_sale_qty' => '0.0000',
                'use_config_enable_qty_inc' => '1', 'enable_qty_increments' => '0',
                'use_config_qty_increments' => '1', 'qty_increments' => '0.0000'
            ]]],
            [378, [378 => [
                'entity_id' => '378', 'qty' => '13.0000', 'use_config_min_sale_qty' => '1',
                'min_sale_qty' => '1.0000', 'use_config_max_sale_qty' => '1', 'max_sale_qty' => '0.0000',
                'use_config_enable_qty_inc' => '1', 'enable_qty_increments' => '0',
                'use_config_qty_increments' => '1', 'qty_increments' => '0.0000'
            ]]],
        ];
    }

    /**
     * @covers ::addTierPricesToSelect
     * @param   int     $websiteId
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddTierPricesToSelectDataProvider
     */
    public function testAddTierPricesToSelect($websiteId, $productIds, $expectedItems)
    {
        $this->_collection
            ->addTierPricesToSelect($websiteId)
            ->addIdFilter($productIds);

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddTierPricesToSelectDataProvider()
    {
        return [
            [1, 390, [390 => ['entity_id' => '390', 'tier_prices' => '2|70.00,3|65.00']]],
            [1, 381, [381 => ['entity_id' => '381', 'tier_prices' => '2|110.00,3|100.00']]],
            [1, 536, [536 => ['entity_id' => '536', 'tier_prices' => '']]],
        ];
    }

    /**
     * @coversNothing
     * @param   array   $productIds
     * @param   int     $storeId
     * @param   array   $attributeCodes
     * @param   array   $expectedItems
     * @dataProvider getTestAllFiltersTogetherDataProvider
     */
    public function testAllFiltersTogether($productIds, $storeId, $attributeCodes, $expectedItems)
    {
        /** @var \MiraklSeller_Core_Model_Listing|\PHPUnit_Framework_MockObject_MockObject $listingMock */
        $listingMock = $this->getMockBuilder(\MiraklSeller_Core_Model_Listing::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['__call', 'getData', 'getWebsiteId'])
            ->getMock();

        $listingMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);

        foreach ($attributeCodes as $attrCode) {
            $this->_collection->addAttributeOptionValue($this->_getAttribute($attrCode));
        }

        $this->_collection
            ->addQuantityToSelect()
            ->addTierPricesToSelect($listingMock->getWebsiteId())
            ->addListingPriceData($listingMock)
            ->addIdFilter($productIds);

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAllFiltersTogetherDataProvider()
    {
        return [
            [
                360, 0, ['color', 'size', 'electronic_type'], [
                    360 => [
                        'entity_id' => '360', 'color' => 'Cognac', 'size' => null, 'electronic_type' => null,
                        'qty' => '25.0000', 'use_config_min_sale_qty' => '1', 'min_sale_qty' => '1.0000',
                        'use_config_max_sale_qty' => '1', 'max_sale_qty' => '0.0000',
                        'use_config_enable_qty_inc' => '1', 'enable_qty_increments' => '0',
                        'use_config_qty_increments' => '1', 'qty_increments' => '0.0000',
                        'tier_prices' => '', 'price' => '375.0000', 'tax_class_id' => '2',
                        'final_price' => '375.0000', 'minimal_price' => '375.0000', 'min_price' => '375.0000',
                        'max_price' => '375.0000', 'tier_price' => null
                    ],
                ],
            ],
            [
                365, 1, ['color', 'size', 'decor_type'], [
                    365 => [
                        'entity_id' => '365', 'color' => 'Blue', 'size' => null, 'decor_type' => null,
                        'qty' => '25.0000', 'use_config_min_sale_qty' => '1', 'min_sale_qty' => '1.0000',
                        'use_config_max_sale_qty' => '1', 'max_sale_qty' => '0.0000',
                        'use_config_enable_qty_inc' => '1', 'enable_qty_increments' => '0',
                        'use_config_qty_increments' => '1', 'qty_increments' => '0.0000',
                        'tier_prices' => '', 'price' => '310.0000', 'tax_class_id' => '2',
                        'final_price' => '310.0000', 'minimal_price' => '310.0000', 'min_price' => '310.0000',
                        'max_price' => '310.0000', 'tier_price' => null
                    ],
                ],
            ],
            [
                390, 1, ['color', 'material', 'home_decor_type'], [
                    390 => [
                        'entity_id' => '390', 'color' => 'Charcoal', 'material' => 'Wood',
                        'home_decor_type' => 'Decorative Accents', 'qty' => '25.0000', 'use_config_min_sale_qty' => '1',
                        'min_sale_qty' => '1.0000', 'use_config_max_sale_qty' => '1', 'max_sale_qty' => '0.0000',
                        'use_config_enable_qty_inc' => '1', 'enable_qty_increments' => '0',
                        'use_config_qty_increments' => '1', 'qty_increments' => '0.0000', 'tier_prices' => '2|70.00,3|65.00',
                        'price' => '75.0000', 'tax_class_id' => '2', 'final_price' => '75.0000',
                        'minimal_price' => '65.0000', 'min_price' => '75.0000', 'max_price' => '75.0000',
                        'tier_price' => '65.0000'
                    ],
                ],
            ],
            [
                98656217, 2, ['luggage_size'], []
            ],
        ];
    }

    /**
     * @covers ::addCategoryIds
     * @param   array   $productIds
     * @param   bool    $fallbackToParent
     * @param   array   $expectedItems
     * @dataProvider getTestAddCategoryIdsDataProvider
     */
    public function testAddCategoryIds($productIds, $fallbackToParent, $expectedItems)
    {
        $this->_collection->addIdFilter($productIds);
        $this->_collection->load();
        $this->_collection->addCategoryIds($fallbackToParent);

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddCategoryIdsDataProvider()
    {
        return [
            [
                [372, 374, 377], true, [
                    372 => ['entity_id' => '372', 'category_ids' => [21]],
                    374 => ['entity_id' => '374', 'category_ids' => [9, 21]],
                    377 => ['entity_id' => '377', 'category_ids' => [21]],
                ],
            ],
            [
                [255, 256, 257], true, [
                    255 => ['entity_id' => '255', 'category_ids' => [16]],
                    256 => ['entity_id' => '256', 'category_ids' => [16]],
                    257 => ['entity_id' => '257', 'category_ids' => [16]],
                ],
            ],
            [
                [547, 548, 549, 551, 552, 553, 554], true, [
                    547 => ['entity_id' => '547', 'category_ids' => [19]],
                    548 => ['entity_id' => '548', 'category_ids' => [19]],
                    549 => ['entity_id' => '549', 'category_ids' => [19]],
                    551 => ['entity_id' => '551', 'category_ids' => [19]],
                    552 => ['entity_id' => '552', 'category_ids' => [19]],
                    553 => ['entity_id' => '553', 'category_ids' => [19]],
                    554 => ['entity_id' => '554', 'category_ids' => [19]],
                ],
            ],
            [
                [547, 548, 549, 551, 552, 553, 554], false, [
                    547 => ['entity_id' => '547'],
                    548 => ['entity_id' => '548'],
                    549 => ['entity_id' => '549', 'category_ids' => [19]],
                    551 => ['entity_id' => '551', 'category_ids' => [19]],
                    552 => ['entity_id' => '552', 'category_ids' => [19]],
                    553 => ['entity_id' => '553', 'category_ids' => [19]],
                    554 => ['entity_id' => '554', 'category_ids' => [19]],
                ],
            ],
            [
                [], true, [],
            ],
        ];
    }

    /**
     * @covers ::addCategoryNames
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddCategoryNamesDataProvider
     */
    public function testAddCategoryNames($productIds, $expectedItems)
    {
        $this->_collection->addIdFilter($productIds);
        $this->_collection->load();
        $this->_collection->addCategoryNames();

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddCategoryNamesDataProvider()
    {
        return [
            [
                [372, 374, 377], [
                    372 => ['entity_id' => '372', 'category_names' => ['Bags & Luggage']],
                    374 => ['entity_id' => '374', 'category_names' => ['VIP', 'Bags & Luggage']],
                    377 => ['entity_id' => '377', 'category_names' => ['Bags & Luggage']],
                ],
            ],
            [
                [255, 256, 257], [
                    255 => ['entity_id' => '255', 'category_names' => ['Tees, Knits and Polos']],
                    256 => ['entity_id' => '256', 'category_names' => ['Tees, Knits and Polos']],
                    257 => ['entity_id' => '257', 'category_names' => ['Tees, Knits and Polos']],
                ],
            ],
            [
                [], [],
            ],
        ];
    }

    /**
     * @covers ::addParentSkus
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getTestAddParentSkusDataProvider
     */
    public function testAddParentSkus($productIds, $expectedItems)
    {
        $this->_collection->addIdFilter($productIds);
        $this->_collection->load();
        $this->_collection->overrideByParentData(null, ['parent_sku' => 'sku']);

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getTestAddParentSkusDataProvider()
    {
        return [
            [
                [372, 374, 377], [
                    372 => ['entity_id' => '372'],
                    374 => ['entity_id' => '374'],
                    377 => ['entity_id' => '377', 'parent_sku' => 'abl006c', 'parent_id' => '436'],
                ],
            ],
            [
                [255, 256, 257], [
                    255 => ['entity_id' => '255', 'parent_sku' => 'mtk006c', 'parent_id' => '411'],
                    256 => ['entity_id' => '256', 'parent_sku' => 'mtk006c', 'parent_id' => '411'],
                    257 => ['entity_id' => '257', 'parent_sku' => 'mtk006c', 'parent_id' => '411'],
                ],
            ],
            [
                [], [],
            ],
        ];
    }

    /**
     * @covers ::addMediaGalleryAttribute
     * @param   array   $productIds
     * @param   array   $expectedItems
     * @dataProvider getAddMediaGalleryAttributeDataProvider
     */
    public function testAddMediaGalleryAttribute($productIds, $expectedItems)
    {
        self::mockBaseUrl();

        $this->_collection->addIdFilter($productIds);
        $this->_collection->load();
        $this->_collection->addMediaGalleryAttribute();

        $this->assertSame($expectedItems, $this->_collection->getItems());
    }

    /**
     * @return  array
     */
    public function getAddMediaGalleryAttributeDataProvider()
    {
        return [
            [
                [372, 374, 377], [
                    372 => ['entity_id' => '372', 'image_1' => 'http://foobar.com/catalog/product/a/b/abl002b_1.jpg'],
                    374 => ['entity_id' => '374', 'image_1' => 'http://foobar.com/catalog/product/a/b/abl004a_1.jpg'],
                    377 => ['entity_id' => '377', 'image_1' => 'http://foobar.com/catalog/product/a/b/abl0006a_3.jpg'],
                ],
            ],
            [
                [255, 256, 257], [
                    255 => ['entity_id' => '255', 'image_1' => 'http://foobar.com/catalog/product/m/t/mtk006t_1.jpg'],
                    256 => ['entity_id' => '256', 'image_1' => 'http://foobar.com/catalog/product/m/t/mtk006t_2.jpg'],
                    257 => ['entity_id' => '257', 'image_1' => 'http://foobar.com/catalog/product/m/t/mtk006t_3.jpg'],
                ],
            ],
            [
                [], [],
            ],
        ];
    }

    /**
     * @param   string  $attrCode
     * @return  \Mage_Catalog_Model_Resource_Eav_Attribute
     */
    protected function _getAttribute($attrCode)
    {
        return $this->_productResource->getAttribute($attrCode);
    }
}