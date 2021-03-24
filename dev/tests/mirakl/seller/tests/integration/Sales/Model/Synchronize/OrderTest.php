<?php
namespace Mirakl\Test\Integration\Sales\Model\Synchronize;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Common\Domain\Order\OrderState;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use Mirakl\Test\Integration\Sales;

/**
 * @group sales
 * @group model
 * @group order
 * @coversDefaultClass \MiraklSeller_Sales_Model_Synchronize_Order
 */
class OrderTest extends Sales\TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Sales_Model_Synchronize_Order
     */
    protected $_synchronizeOrder;

    public function setUp()
    {
        parent::setUp();
        $this->_synchronizeOrder = \Mage::getModel('mirakl_seller_sales/synchronize_order');
    }

    /**
     * @covers ::synchronize
     */
    public function testSynchronize()
    {
        self::mockConfigValues([
            'mirakl_seller_sales/order/auto_create_invoice'  => 0,
            'mirakl_seller_sales/order/auto_create_shipment' => 0,
            'carriers/flatrate/handling_fee'                 => 0,
            'carriers/ups/active'                            => 0,
            'carriers/usps/active'                           => 0,
            'carriers/fedex/active'                          => 0,
            'carriers/dhlint/active'                         => 0,
        ]);

        $miraklOrdersData = $this->_getJsonFileContents('OR11.json');
        $miraklOrder = ShopOrder::create($miraklOrdersData['orders'][0]);

        /** @var \Mage_Sales_Model_Order $magentoOrder */
        $magentoOrder = $this->createMagentoOrder($miraklOrder, $this->_createSampleConnection());

        $this->assertSame(OrderState::SHIPPING, $miraklOrder->getStatus()->getState());

        $this->assertEquals(0, $magentoOrder->getInvoiceCollection()->count());
        $this->assertEquals(0, $magentoOrder->getShipmentsCollection()->count());

        \AspectMock\Test::clean();

        Test::double(\MiraklSeller_Api_Helper_Shipment::class, [
            'getShipments' => (new SeekableCollection)->setCollection(new MiraklCollection()),
        ]);

        self::mockConfigValues([
            'mirakl_seller_sales/order/auto_create_invoice' => 1,
            'mirakl_seller_sales/order/auto_create_shipment' => 1,
        ]);

        $updated = $this->_synchronizeOrder->synchronize($magentoOrder, $miraklOrder);

        $this->assertTrue($updated);

        // Need to reload Magento order because of invoices and shipments caching
        $magentoOrder = \Mage::getModel('sales/order')->load($magentoOrder->getId());
        $this->assertEquals(1, $magentoOrder->getInvoiceCollection()->count());
        $this->assertEquals(0, $magentoOrder->getShipmentsCollection()->count());
    }
}