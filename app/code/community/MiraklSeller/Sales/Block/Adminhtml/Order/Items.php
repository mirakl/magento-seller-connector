<?php

use Mirakl\MMP\Common\Domain\Order\OrderState;

class MiraklSeller_Sales_Block_Adminhtml_Order_Items extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * @var MiraklSeller_Sales_Helper_Order
     */
    protected $_helper;

    /**
     * Initialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('mirakl_order_items_grid');
        $this->setSaveParametersInSession(true);
        $this->_pagerVisibility = false;
        $this->_filterVisibility = false;
        $this->_helper = Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * @return  bool
     */
    protected function _canMassAcceptOrderLines()
    {
        if ($this->_isOrderWaitingAcceptance()) {
            /** @var Varien_Object $item */
            foreach ($this->getCollection() as $item) {
                if ($item->getData('product_id')) {
                    return true; // If one Magento product is found, we can accept at least one item
                }
            }
        }

        return false;
    }

    /**
     * @param   mixed           $value
     * @param   Varien_Object   $row
     * @return  string
     */
    public function decorateProduct($value, $row)
    {
        if ($row->getProductId()) {
            $productUrl = $this->getUrl('*/catalog_product/edit', array('id' => $row->getProductId()));

            return sprintf('<a href="%s">%s</a>', $productUrl, $row->getOfferSku());
        }

        return sprintf('<span class="grid-severity-critical"><span>%s</span></span>', $this->__('Not Found'));
    }

    /**
     * @param   string          $qty
     * @param   Varien_Object   $row
     * @return  string
     */
    public function decorateQuantity($qty, $row)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $row->getProduct();
        if ($product->getId() && $this->_isOrderWaitingAcceptance()) {
            /** @var Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $qty .= sprintf(
                '<br><span class="nobr%s">%s</span>',
                (!$stockItem->getIsInStock() || $stockItem->getQty() < $row->getQuantity()) ? ' red' : '',
                $stockItem->getIsInStock() ? $this->__('%d in stock', $stockItem->getQty()) : $this->__('out of stock')
            );
        }

        return $qty;
    }

    /**
     * @param   string          $sku
     * @param   Varien_Object   $row
     * @return  string
     */
    public function decorateSku($sku, $row)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $row->getProduct();

        if ($product->isDisabled()) {
            $sku .= sprintf('<br><span class="nobr red">%s</span>', $this->__('disabled'));
        }

        return $sku;
    }

    /**
     * @param   string  $value
     * @return  string
     */
    public function decorateStatus($value)
    {
        switch ($value) {
            case OrderState::CANCELED:
            case OrderState::REFUSED:
            case OrderState::REFUNDED:
                $class = 'grid-severity-critical';
                break;
            case OrderState::CLOSED:
            case OrderState::RECEIVED:
                $class = 'grid-severity-notice';
                break;
            default:
                $class = 'grid-severity-minor';
        }

        $statusList = Mage::helper('mirakl_seller_sales')->getOrderStatusList();
        $value = isset($statusList[$value]) ? $statusList[$value] : $value;

        return sprintf('<span class="%s"><span>%s</span></span>', $class, $value);
    }

    /**
     * @return  MiraklSeller_Sales_Block_Adminhtml_Order_Item_Column_Renderer_Massaction
     */
    protected function _getMassActionColumnRenderer()
    {
        return $this->getLayout()->createBlock('mirakl_seller_sales/adminhtml_order_item_column_renderer_massaction');
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getMiraklConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * @return  \Mirakl\MMP\Shop\Domain\Order\ShopOrder
     */
    public function getMiraklOrder()
    {
        return Mage::registry('mirakl_seller_order');
    }

    /**
     * @return  bool
     */
    protected function _isOrderWaitingAcceptance()
    {
        return $this->getMiraklOrder()->getStatus()->getState() === OrderState::WAITING_ACCEPTANCE;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $order = $this->getMiraklOrder();
        if ($this->_collection || !$order) {
            return $this;
        }

        $collection = new Varien_Data_Collection();
        $this->setCollection($collection);

        try {
            /** @var \Mirakl\MMP\Common\Domain\Order\ShopOrderLine $orderLine */
            foreach ($order->getOrderLines() as $orderLine) {
                $data = $orderLine->getData();
                $data['currency_iso_code'] = $order->getCurrencyIsoCode();
                $data['offer_id']          = $orderLine->getOffer()->getId();
                $data['offer_sku']         = $orderLine->getOffer()->getSku();
                $data['product_name']      = $orderLine->getOffer()->getProduct()->getTitle();
                $data['shipping_title']    = $order->getShipping()->getType()->getLabel();
                $data['status']            = $orderLine->getStatus()->getState();
                $data['unit_price']        = $orderLine->getOffer()->getPrice();
                $data['subtotal']          = $orderLine->getPrice();
                $data['tax']               = $this->_helper->getMiraklOrderLineTaxAmount($orderLine, true);
                $data['total_price']       = $data['subtotal'] + $data['shipping_price'] + $data['tax'];

                // Try to find attached product in Magento
                $data['product_image'] = '';
                $product = Mage::getModel('catalog/product');
                $productId = $product->getResource()->getIdBySku($data['offer_sku']);
                $data['product'] = $product;
                $data['product_id'] = $productId;
                if ($productId) {
                    $product->load($productId);
                    $imageHelper = new Mage_Catalog_Helper_Image(); // Do not use Mage::helper() to reset image model cache
                    try {
                        $data['product_image'] = $imageHelper->init($product, 'thumbnail')->resize(75);
                    } catch (Exception $e) {
                        // Ignore any exception on image
                    }
                }

                $collection->addItem(new Varien_Object($data));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_image', array(
                'header'         => $this->__('Image'),
                'index'          => 'product_image',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateImage'),
                'width'          => '1px',
            )
        );

        $this->addColumn(
            'status', array(
                'header'         => $this->__('Mirakl Status'),
                'index'          => 'status',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateStatus'),
                'width'          => '1px',
            )
        );

        $this->addColumn(
            'offer_id', array(
                'header'          => $this->__('Offer Id'),
                'index'           => 'offer_id',
                'filter'          => false,
                'sortable'        => false,
                'html_decorators' => array('nobr'),
            )
        );

        $this->addColumn(
            'offer_sku', array(
                'header'         => $this->__('Offer SKU'),
                'index'          => 'offer_sku',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateSku'),
            )
        );

        $this->addColumn(
            'product_name', array(
                'header'          => $this->__('Product Name'),
                'index'           => 'product_name',
                'filter'          => false,
                'sortable'        => false,
                'html_decorators' => array('nobr'),
            )
        );

        $this->addColumn(
            'magento_product', array(
                'header'         => $this->__('Magento Product'),
                'width'          => '80px',
                'filter'         => false,
                'sortable'       => false,
                'align'          => 'center',
                'frame_callback' => array($this, 'decorateProduct'),
            )
        );

        $this->addColumn(
            'quantity', array(
                'header'         => $this->__('Qty'),
                'index'          => 'quantity',
                'type'           => 'number',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateQuantity'),
            )
        );

        $this->addColumn(
            'price', array(
                'header'   => $this->__('Price Excl. Tax'),
                'index'    => 'unit_price',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'subtotal', array(
                'header'   => $this->__('Subtotal Excl. Tax'),
                'index'    => 'subtotal',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'shipping_price', array(
                'header'   => $this->__('Shipping Price Excl. Tax'),
                'index'    => 'shipping_price',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'shipping_title', array(
                'header'   => $this->__('Shipping Title'),
                'index'    => 'shipping_title',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'tax', array(
                'header'   => $this->__('Tax Amount'),
                'index'    => 'tax',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'total_price', array(
                'header'   => $this->__('Total'),
                'index'    => 'total_price',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareMassaction()
    {
        $this->_prepareCollection(); // Collection is needed to check if accept action is available

        if (!$this->_canMassAcceptOrderLines()) {
            return $this;
        }

        $this->setMassactionIdField('id');
        $this->setMassactionIdFilter('id');
        $this->getMassactionBlock()->setFormFieldName('order_lines')->setUseSelectAll(false);

        $massAcceptUrl = $this->getUrl(
            '*/mirakl_seller_order/massAccept', array(
                'connection_id' => $this->getMiraklConnection()->getId(),
                'order_id'      => $this->getMiraklOrder()->getId(),
            )
        );

        $this->getMassactionBlock()->addItem(
            'accept', array(
                'label'    => $this->__('Accept in Mirakl'),
                'url'      => $massAcceptUrl,
                'selected' => true,
                'confirm'  => $this->__('Selected lines will be ACCEPTED and the others will be REFUSED in Mirakl.'),
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareMassactionColumn()
    {
        parent::_prepareMassactionColumn();

        /** @var Mage_Adminhtml_Block_Widget_Grid_Column $massActionCol */
        $massActionCol = $this->getColumn('massaction');
        $renderer = $this->_getMassActionColumnRenderer();
        $renderer->setColumn($massActionCol);
        $massActionCol->setRenderer($renderer);

        $values = array();

        /** @var Varien_Object $item */
        foreach ($this->getCollection() as $item) {
            if ($item->getData('product_id')) {
                $values[] = $item->getData('id');
            }
        }

        if (!empty($values)) {
            // Init selected checkboxes
            $massActionCol->setValues($values);
            $key = $this->getMassactionBlock()->getFormFieldNameInternal();
            $this->getRequest()->setParam($key, implode(',', $values));
        }

        return $this;
    }

    /**
     * @param   string  $value
     * @return  string
     */
    public function decorateImage($value)
    {
        if (!$value) {
            return '';
        }

        return sprintf('<img src="%s" alt="" />', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridHeader()
    {
        return Mage::helper('sales')->__('Items Ordered');
    }

    /**
     * {@inheritdoc}
     */
    public function getRowClass($row)
    {
        $class = array();

        /** @var Varien_Object $row */
        if (!$row->getData('product_id')) {
            $class[] = 'invalid';
        }

        return implode(' ', $class);
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return '#';
    }
}
