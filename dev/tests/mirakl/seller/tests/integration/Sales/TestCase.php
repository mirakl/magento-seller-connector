<?php
namespace Mirakl\Test\Integration\Sales;

use AspectMock\Test;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

abstract class TestCase extends \Mirakl\Test\Integration\TestCase
{
    /**
     * @var \MiraklSeller_Sales_Helper_Order
     */
    protected $_orderHelper;

    /**
     * @var array
     */
    protected $_createdOrderIds = [];

    public function setUp()
    {
        $this->_orderHelper = \Mage::helper('mirakl_seller_sales/order');
    }

    protected function tearDown()
    {
        if (!empty($this->_createdOrderIds)) {
            \Mage::getModel('sales/order')->getCollection()
                ->addFieldToFilter('entity_id', ['in' => $this->_createdOrderIds])
                ->walk('delete');
        }

        Test::clean();
    }

    /**
     * @param   ShopOrder   $miraklOrder
     * @return  \Mage_Sales_Model_Order
     */
    protected function createMagentoOrder(ShopOrder $miraklOrder)
    {
        $magentoOrder = $this->_orderHelper->createOrder($miraklOrder);
        $this->_createdOrderIds[] = $magentoOrder->getId();

        return $magentoOrder;
    }
}