<?php
namespace Mirakl\Test\Integration\Api\Model\Client;

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
     * @covers ::get
     */
    public function testGetMethod()
    {
        /** @var \MiraklSeller_Api_Model_Connection $connection */
        $connection = \Mage::getModel('mirakl_seller_api/connection');
        $connection->setId(1);
        $connection->setName('Test 1');
        $connection->setApiUrl('http://test1.mirakl.net/api');
        $connection->setApiKey('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx');

        $mci1 = $this->_clientManager->get($connection, 'MCI');
        $mci2 = $this->_clientManager->get($connection, 'MCI');
        $this->assertSame($mci1, $mci2);

        $connection->setId(2);
        $mci3 = $this->_clientManager->get($connection, 'MCI');
        $this->assertNotSame($mci1, $mci3);

        $mmp1 = $this->_clientManager->get($connection, 'MMP');
        $this->assertNotSame($mci2, $mmp1);

        $mmp2 = $this->_clientManager->get($connection, 'MMP');
        $this->assertSame($mmp1, $mmp2);

        $connection->setId(3);
        $mmp3 = $this->_clientManager->get($connection, 'MMP');
        $this->assertNotSame($mmp1, $mmp3);

        $connection->setName('Test 2');
        $mmp4 = $this->_clientManager->get($connection, 'MMP');
        $this->assertSame($mmp3, $mmp4);
    }
}