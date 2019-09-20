<?php
namespace Mirakl\Test\Unit\Api\Helper;

use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group helper
 * @coversDefaultClass \MiraklSeller_Api_Helper_Offer
 */
class OfferTest extends TestCase
{
    /**
     * @var \MiraklSeller_Api_Helper_Offer
     */
    protected $_helper;

    protected function setUp()
    {
        $this->_helper = \Mage::helper('mirakl_seller_api/offer');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No offer to import
     */
    public function testImportOffersWithEmptyData()
    {
        /** @var \MiraklSeller_Api_Model_Connection|\PHPUnit_Framework_MockObject_MockObject $connectionMock */
        $connectionMock = $this->createMock(\MiraklSeller_Api_Model_Connection::class);

        $this->_helper->importOffers($connectionMock, []);
    }
}