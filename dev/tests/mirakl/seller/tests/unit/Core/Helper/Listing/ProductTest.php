<?php
namespace Mirakl\Test\Unit\Core\Helper\Listing;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group helper
 * @coversDefaultClass \MiraklSeller_Core_Helper_Listing_Product
 */
class ProductTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Core_Helper_Listing_Product
     */
    protected $_helper;

    protected function setUp()
    {
        $this->_helper = \Mage::helper('mirakl_seller/listing_product');
    }

    protected function tearDown()
    {
        Test::clean();
    }

    /**
     * @covers ::getCategoryFromPaths
     * @param   array   $paths
     * @param   mixed   $expected
     * @dataProvider getGetCategoryFromPathsDataProvider
     */
    public function testGetCategoryFromPaths(array $paths, $expected)
    {
        $this->assertSame($expected, $this->_helper->getCategoryFromPaths($paths));
    }

    /**
     * @return  array
     */
    public function getGetCategoryFromPathsDataProvider()
    {
        return [
            [
                [],
                false,
            ],
            [
                [
                    ['foo', 'bar', 'baz'],
                    ['foo', 'bar'],
                ],
                ['foo', 'bar', 'baz']
            ],
            [
                [
                    ['b', 'foo', 'bar', 'baz'],
                    ['a', 'foo', 'bar', 'baz'],
                ],
                ['a', 'foo', 'bar', 'baz']
            ],
            [
                [
                    ['b', 'foo', 'bar', 'baz'],
                    ['a', 'foo', 'bar', 'baz'],
                    ['Lorem', 'ipsum', 'dolor', 'sit', 'amet'],
                    ['Lorem', 'ipsum'],
                ],
                ['Lorem', 'ipsum', 'dolor', 'sit', 'amet']
            ],
            [
                [
                    ['A', 'B', 'C', 'D'],
                    ['A', 'B', 'B', 'D'],
                    ['A', 'A', 'C', 'D'],
                ],
                ['A', 'B', 'B', 'D']
            ],
            [
                [
                    ['A', 'B', 'C', 'D'],
                    ['A', 'B', 'C', 'D'],
                    ['A', 'A', 'C', 'D'],
                ],
                ['A', 'A', 'C', 'D']
            ],
            [
                [
                    ['A', 'B', 'C', 'D'],
                    ['A', 'B', 'C', 'D'],
                    ['A', 'A', 'C', 'D'],
                ],
                ['A', 'A', 'C', 'D']
            ],
        ];
    }
}