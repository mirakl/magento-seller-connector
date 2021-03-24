<?php
namespace Mirakl\Test\Integration\Sales\Model\Synchronize;

use Mirakl\Aspect\AspectMockTrait;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;
use Mirakl\Test\Integration\Sales;

/**
 * @group sales
 * @group model
 * @group refunds
 * @coversDefaultClass \MiraklSeller_Sales_Model_Synchronize_Refunds
 */
class RefundsTest extends Sales\TestCase
{
    use AspectMockTrait;

    /**
     * @var \MiraklSeller_Sales_Model_Synchronize_Refunds
     */
    protected $_synchronizeRefunds;

    public function setUp()
    {
        parent::setUp();
        $this->_synchronizeRefunds = \Mage::getModel('mirakl_seller_sales/synchronize_refunds');
    }

    /**
     * @covers ::synchronize
     */
    public function testSynchronize()
    {
        self::mockConfigValues([
            'mirakl_seller_sales/order/auto_create_invoice'  => 1,
            'mirakl_seller_sales/order/auto_create_shipment' => 0,
            'carriers/flatrate/handling_fee'                 => 0,
            'carriers/ups/active'                            => 0,
            'carriers/usps/active'                           => 0,
            'carriers/fedex/active'                          => 0,
            'carriers/dhlint/active'                         => 0,
        ]);

        $miraklOrdersData = $this->_getJsonFileContents('OR11.json');
        $miraklOrder = ShopOrder::create($miraklOrdersData['orders'][0]);

        $magentoOrder = $this->createMagentoOrder($miraklOrder, $this->_createSampleConnection());

        $this->assertTrue($magentoOrder->canCreditmemo());

        $updated = $this->_synchronizeRefunds->synchronize($magentoOrder, $miraklOrder);

        $this->assertTrue($updated);

        $creditMemos = $magentoOrder->getCreditmemosCollection()->getItems();
        $this->assertCount(3, $creditMemos);

        /** @var \Mage_Sales_Model_Order_Creditmemo $creditMemo1 */
        $creditMemo1 = current($creditMemos);
        $this->assertEquals(1120, $creditMemo1->getMiraklRefundId());
        $this->assertEquals(2.81, $creditMemo1->getSubtotal());
        $this->assertEquals(2.81, $creditMemo1->getSubtotalInclTax());
        $this->assertEquals(2.81, $creditMemo1->getGrandTotal());
        $this->assertEquals(0, $creditMemo1->getTaxAmount());
        $this->assertEquals(0, $creditMemo1->getShippingAmount());
        $this->assertEquals(0, $creditMemo1->getShippingTaxAmount());
        $this->assertEquals(0, $creditMemo1->getShippingInclTax());

        /** @var \Mage_Sales_Model_Order_Creditmemo_Item $creditMemo1Item1 */
        $creditMemo1Item1 = $creditMemo1->getItemsCollection()->getFirstItem();
        $this->assertEquals('ace000', $creditMemo1Item1->getSku());
        $this->assertEquals('Aviator Sunglasses', $creditMemo1Item1->getName());
        $this->assertEquals(1, $creditMemo1Item1->getQty());
        $this->assertEquals(2.81, $creditMemo1Item1->getPrice());
        $this->assertEquals(2.81, $creditMemo1Item1->getRowTotal());
        $this->assertEquals(2.81, $creditMemo1Item1->getRowTotalInclTax());
        $this->assertEquals(2.81, $creditMemo1Item1->getPriceInclTax());
        $this->assertEquals(0, $creditMemo1Item1->getTaxAmount());

        /** @var \Mage_Sales_Model_Order_Creditmemo $creditMemo2 */
        $creditMemo2 = next($creditMemos);
        $this->assertEquals(1121, $creditMemo2->getMiraklRefundId());
        $this->assertEquals(20, $creditMemo2->getSubtotal());
        $this->assertEquals(30, $creditMemo2->getSubtotalInclTax());
        $this->assertEquals(30.37, $creditMemo2->getGrandTotal());
        $this->assertEquals(10.17, $creditMemo2->getTaxAmount());
        $this->assertEquals(0.20, $creditMemo2->getShippingAmount());
        $this->assertEquals(0.17, $creditMemo2->getShippingTaxAmount());
        $this->assertEquals(0.37, $creditMemo2->getShippingInclTax());

        /** @var \Mage_Sales_Model_Order_Creditmemo_Item $creditMemo2Item1 */
        $creditMemo2Item1 = $creditMemo2->getItemsCollection()->getFirstItem();
        $this->assertEquals('ace002', $creditMemo2Item1->getSku());
        $this->assertEquals('Retro Chic Eyeglasses', $creditMemo2Item1->getName());
        $this->assertEquals(1, $creditMemo2Item1->getQty());
        $this->assertEquals(20, $creditMemo2Item1->getPrice());
        $this->assertEquals(20, $creditMemo2Item1->getRowTotal());
        $this->assertEquals(30, $creditMemo2Item1->getRowTotalInclTax());
        $this->assertEquals(30, $creditMemo2Item1->getPriceInclTax());
        $this->assertEquals(10, $creditMemo2Item1->getTaxAmount());

        /** @var \Mage_Sales_Model_Order_Creditmemo $creditMemo3 */
        $creditMemo3 = next($creditMemos);
        $this->assertEquals(1122, $creditMemo3->getMiraklRefundId());
        $this->assertEquals(0, $creditMemo3->getSubtotal());
        $this->assertEquals(0, $creditMemo3->getSubtotalInclTax());
        $this->assertEquals(0.06, $creditMemo3->getGrandTotal());
        $this->assertEquals(0.05, $creditMemo3->getTaxAmount());
        $this->assertEquals(0.01, $creditMemo3->getShippingAmount());
        $this->assertEquals(0.05, $creditMemo3->getShippingTaxAmount());
        $this->assertEquals(0.06, $creditMemo3->getShippingInclTax());
        $this->assertEquals(0, $creditMemo3->getItemsCollection()->count());

        $updated = $this->_synchronizeRefunds->synchronize($magentoOrder, $miraklOrder);

        $this->assertFalse($updated);
    }
}