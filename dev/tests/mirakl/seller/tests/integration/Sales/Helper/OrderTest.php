<?php
namespace Mirakl\Test\Integration\Sales\Helper;

use AspectMock\Test;
use Mirakl\Aspect\AspectMockTrait;
use Mirakl\Core\Domain\Collection\MiraklCollection;
use Mirakl\MMP\Common\Domain\Collection\SeekableCollection;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use Mirakl\Test\Integration\Sales;

/**
 * @group sales
 * @group helper
 * @group order
 * @coversDefaultClass \MiraklSeller_Sales_Helper_Order
 */
class OrderTest extends Sales\TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Sales_Helper_Order
     */
    protected $_orderHelper;

    public function setUp()
    {
        parent::setUp();
        $this->_orderHelper = \Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * @covers ::createOrder
     */
    public function testCreateOrder()
    {
        Test::double(\MiraklSeller_Api_Helper_Shipment::class, [
            'getShipments' => (new SeekableCollection)->setCollection(new MiraklCollection()),
        ]);

        self::mockConfigValues([
            'mirakl_seller_sales/order/auto_create_invoice'  => 1,
            'mirakl_seller_sales/order/auto_create_shipment' => 1,
            'carriers/flatrate/handling_fee'                 => 0,
            'carriers/ups/active'                            => 0,
            'carriers/usps/active'                           => 0,
            'carriers/fedex/active'                          => 0,
            'carriers/dhlint/active'                         => 0,
        ]);

        $miraklOrdersData = $this->_getJsonFileContents('OR11.json');
        $miraklOrder = ShopOrder::create($miraklOrdersData['orders'][0]);

        $magentoOrder = $this->createMagentoOrder($miraklOrder, $this->_createSampleConnection());

        $this->assertSame(\Mage_Sales_Model_Order::STATE_PROCESSING, $magentoOrder->getState());
        $this->assertEquals(1, $magentoOrder->getInvoiceCollection()->count());
        $this->assertEquals(0, $magentoOrder->getShipmentsCollection()->count());
        $this->assertEquals(5, $magentoOrder->getTotalQtyOrdered());
        $this->assertEquals(100.24, $magentoOrder->getSubtotal());
        $this->assertEquals(123.91, $magentoOrder->getSubtotalInclTax());
        $this->assertEquals(14, $magentoOrder->getShippingAmount());
        $this->assertEquals(3.73, $magentoOrder->getShippingTaxAmount());
        $this->assertEquals(141.64, $magentoOrder->getGrandTotal());
        $this->assertEquals(27.4, $magentoOrder->getTaxAmount());
        $this->assertEquals(2, $magentoOrder->getItemsCollection()->count());

        $billingAddress = $magentoOrder->getBillingAddress();
        $this->assertSame('Johann', $billingAddress->getFirstname());
        $this->assertSame('Reinké', $billingAddress->getLastname());
        $this->assertSame('45 rue de la Bienfaisance', $billingAddress->getStreet1());
        $this->assertSame('Paris', $billingAddress->getCity());
        $this->assertSame('75008', $billingAddress->getPostcode());
        $this->assertSame('FR', $billingAddress->getCountryId());
        $this->assertSame('0987654321', $billingAddress->getTelephone());

        $shippingAddress = $magentoOrder->getShippingAddress();
        $this->assertSame('Johann', $shippingAddress->getFirstname());
        $this->assertSame('Reinké', $shippingAddress->getLastname());
        $this->assertSame('45 rue de la Bienfaisance', $shippingAddress->getStreet1());
        $this->assertSame('Paris', $shippingAddress->getCity());
        $this->assertSame('75008', $shippingAddress->getPostcode());
        $this->assertSame('FR', $shippingAddress->getCountryId());
        $this->assertSame('0987654321', $shippingAddress->getTelephone());
    }
}