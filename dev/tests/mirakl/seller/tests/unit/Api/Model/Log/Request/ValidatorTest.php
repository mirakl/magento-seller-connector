<?php
namespace Mirakl\Test\Unit\Api\Model\Log\Request;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use MiraklSeller_Api_Model_Log_Options as LogOptions;
use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group model
 * @group log
 * @coversDefaultClass \MiraklSeller_Api_Model_Log_Request_Validator
 */
class ValidatorTest extends TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Api_Model_Log_Request_Validator
     */
    protected $_requestLogValidator;

    /**
     * @var \Mirakl\Core\Request\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_requestMock;

    protected function setUp()
    {
        $this->_requestLogValidator = \Mage::getModel('mirakl_seller_api/log_request_validator');
        $this->_requestMock = $this->createMock(\Mirakl\Core\Request\RequestInterface::class);
    }

    protected function tearDown()
    {
        Test::clean();
    }

    /**
     * @covers ::validate
     */
    public function testValidateWithLoggingDisabled()
    {
        self::mockConfigValue('mirakl_seller_api_developer/log/log_option', LogOptions::LOG_DISABLED);

        $this->assertFalse($this->_requestLogValidator->validate($this->_requestMock));
    }

    /**
     * @covers ::validate
     */
    public function testValidateWithEmptyFilter()
    {
        self::mockConfigValues([
            'mirakl_seller_api_developer/log/log_option' => LogOptions::LOG_REQUESTS_ONLY,
            'mirakl_seller_api_developer/log/log_filter' => '',
        ]);

        $this->assertTrue($this->_requestLogValidator->validate($this->_requestMock));
    }

    /**
     * @covers ::validate
     * @param   string  $filter
     * @param   string  $requestUri
     * @param   array   $requestQueryParams
     * @param   bool    $expected
     * @dataProvider getTestValidateWithFilterDataProvider
     */
    public function testValidateWithFilter($filter, $requestUri, array $requestQueryParams, $expected)
    {
        self::mockConfigValues([
            'mirakl_seller_api_developer/log/log_option' => LogOptions::LOG_REQUESTS_ONLY,
            'mirakl_seller_api_developer/log/log_filter' => $filter,
        ]);

        $this->_requestMock->expects($this->once())
            ->method('getQueryParams')
            ->willReturn($requestQueryParams);
        $this->_requestMock->expects($this->once())
            ->method('getUri')
            ->willReturn($requestUri);

        $this->assertSame($expected, $this->_requestLogValidator->validate($this->_requestMock));
    }

    /**
     * @return  array
     */
    public function getTestValidateWithFilterDataProvider()
    {
        return [
            ['api/orders', 'locales', [], false],
            ['api/orders|api/locales', 'locales', [], true],
            ['api/orders|api/locales', 'orders', [], true],
            ['api/orders\?order_state_codes=WAITING_ACCEPTANCE|api/locales', 'orders', [], false],
            ['api/orders\?order_state_codes=WAITING_ACCEPTANCE|api/locales', 'orders', ['order_state_codes' => 'WAITING_ACCEPTANCE'], true],
        ];
    }
}