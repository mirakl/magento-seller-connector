<?php
namespace Mirakl\Test\Unit\Api\Model;

use AspectMock\Test;
use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group model
 * @group connection
 * @coversDefaultClass \MiraklSeller_Api_Model_Connection
 */
class ConnectionTest extends TestCase
{
    /**
     * @var \MiraklSeller_Api_Model_Connection
     */
    protected $_connectionModel;

    protected function setUp()
    {
        $this->_connectionModel = \Mage::getModel('mirakl_seller_api/connection');
    }

    protected function tearDown()
    {
        Test::clean();
    }

    /**
     * @covers ::validate
     * @param   int     $responseCode
     * @param   string  $expectedExceptionMessage
     * @dataProvider getValidateConnectionWithExceptionDataProvider
     */
    public function testValidateConnectionWithException($responseCode, $expectedExceptionMessage)
    {
        $requestMock = $this->createMock(\GuzzleHttp\Psr7\Request::class);
        $responseMock = $this->createMock(\GuzzleHttp\Psr7\Response::class);
        $responseMock->expects($this->once())
            ->method('getStatusCode')
            ->willReturn($responseCode);
        $exceptionMock = $this->getMockBuilder(\GuzzleHttp\Exception\RequestException::class)
            ->setConstructorArgs(['foo', $requestMock, $responseMock])
            ->getMock();

        $shopApiHelper = \Mage::helper('mirakl_seller_api/shop');
        Test::double($shopApiHelper, ['getAccount' => function() use ($exceptionMock) {
            throw $exceptionMock;
        }]);

        $this->expectException(\Mage_Core_Exception::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->_connectionModel->validate();
    }

    /**
     * @return  array
     */
    public function getValidateConnectionWithExceptionDataProvider()
    {
        return [
            [401, 'CONN-03: You are not authorized to use the API. Please check your API key.'],
            [404, 'CONN-02: The API cannot be reached. Please check the API URL.'],
            [500, 'CONN-01: Unexpected system error. Mirakl cannot be reached.'],
        ];
    }
}