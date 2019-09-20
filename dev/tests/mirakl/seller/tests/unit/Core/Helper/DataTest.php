<?php
namespace Mirakl\Test\Unit\Core\Helper;

use PHPUnit\Framework\TestCase;

/**
 * @group core
 * @group helper
 * @coversDefaultClass \MiraklSeller_Core_Helper_Data
 */
class ProcessTest extends TestCase
{
    /**
     * @var \MiraklSeller_Core_Helper_Data
     */
    protected $_helper;

    protected function setUp()
    {
        $this->_helper = \Mage::helper('mirakl_seller');
    }

    /**
     * @covers ::isDateValid
     * @param   string          $from
     * @param   string          $to
     * @param   \DateTime|null  $date
     * @param   bool            $expected
     * @dataProvider getIsDateValidDataProvider
     */
    public function testIsDateValid($from, $to, $date, $expected)
    {
        $this->assertSame($expected, $this->_helper->isDateValid($from, $to, $date));
    }

    /**
     * @return  array
     */
    public function getIsDateValidDataProvider()
    {
        return [
            ['', '', null, true],
            ['', '', new \DateTime('2017-01-01'), true],
            ['2017-01-01', '', null, true],
            ['2017-01-01', '2017-01-31', null, false],
            ['2017-09-10', '2017-09-08', null, false],
            ['', '2999-12-31', null, true],
            ['2017-09-12', '2018-09-12', new \DateTime('2018-01-01'), true],
            ['2017-09-12', '2018-09-12', new \DateTime('2019-01-01'), false],
            ['2017-01-01', '', new \DateTime('2017-01-01'), true],
            ['2017-01-01', '', new \DateTime('2012-01-01'), false],
            ['', '2017-01-01', new \DateTime('2012-01-01'), true],
            ['', '2017-01-01', new \DateTime('2017-01-01'), true],
            ['', '2017-01-01', new \DateTime('2017-01-02'), false],
            ['2017-01-01', '2017-01-01', new \DateTime('2017-01-01'), true],
            ['2017-01-01', '2017-01-01', new \DateTime('2016-12-31'), false],
        ];
    }
}