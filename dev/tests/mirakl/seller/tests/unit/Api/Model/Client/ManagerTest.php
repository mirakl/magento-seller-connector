<?php
namespace Mirakl\Test\Unit\Api\Model\Client;

use PHPUnit\Framework\TestCase;

/**
 * @group api
 * @group model
 * @coversDefaultClass \MiraklSeller_Api_Model_Client_Manager
 */
class ManagerTest extends TestCase
{
    /**
     * @var \MiraklSeller_Api_Model_Client_Manager
     */
    protected $_clientManager;

    protected function setUp()
    {
        $this->_clientManager = \Mage::getModel('mirakl_seller_api/client_manager');
    }

    /**
     * @covers ::disableClient
     * @expectedException \Mirakl\Core\Exception\ClientDisabledException
     */
    public function testDisableClient()
    {
        /** @var \MiraklSeller_Api_Model_Connection $connection */
        $connection = \Mage::getModel('mirakl_seller_api/connection');
        $connection->setId(1234);

        $client = $this->_clientManager->get($connection, 'MMP');

        \MiraklSeller_Api_Model_Client_Manager::disable();

        /** @var \Mirakl\Core\Request\RequestInterface $requestMock */
        $requestMock = $this->createMock(\Mirakl\Core\Request\RequestInterface::class);

        $client->run($requestMock);
    }
}