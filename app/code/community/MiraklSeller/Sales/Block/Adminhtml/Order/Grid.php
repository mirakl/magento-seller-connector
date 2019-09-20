<?php
/**
 * @method MiraklSeller_Sales_Block_Adminhtml_Order_List getParentBlock()
 */
class MiraklSeller_Sales_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        $this->setId('MiraklOrdersGrid');
        $this->setDefaultSort('created_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('order_filter');
        $this->_helper = Mage::helper('mirakl_seller_sales/order');
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getConnection()
    {
        return $this->getParentBlock()->getCurrentConnection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = new MiraklSeller_Sales_Model_Collection();
        $this->setCollection($collection);

        if (!$connection = $this->getConnection()) {
            return $this;
        }

        try {
            $this->_preparePage();

            $filter = $this->getParam($this->getVarNameFilter(), $this->_defaultFilter);
            if (is_string($filter)) {
                $filter = Mage::helper('adminhtml')->prepareFilterString($filter);
            }

            $params = array();
            foreach ($this->getColumns() as $columnId => $column) {
                if (isset($filter[$columnId])
                    && (!empty($filter[$columnId]) || strlen($filter[$columnId]) > 0)
                    && $column->getFilter()
                ) {
                    $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
                    $value = $filter[$columnId];
                    $column->getFilter()->setValue($value);
                    if ($valueCallback = $column->getFilterValueCallback()) {
                        $value = $valueCallback($value);
                    }
                    $params[$field] = is_array($value) ? implode(',', $value) : $value;
                }
            }

            $page = (int) $this->getParam($this->getVarNamePage(), $this->_defaultPage);
            $offset = ($page - 1) * $collection->getPageSize();
            $limit = $collection->getPageSize();

            // Call OR11 to fetch orders of current connection
            $miraklOrders = Mage::helper('mirakl_seller_api/order')->getOrders($connection, $params, $offset, $limit);

            $collection->setTotalRecords($miraklOrders->getTotalCount());

            $magentoOrders = $this->_helper->getMagentoOrdersByMiraklOrderIds($miraklOrders->walk('getId'));

            /** @var \Mirakl\MMP\Shop\Domain\Order\ShopOrder $miraklOrder */
            foreach ($miraklOrders as $miraklOrder) {
                $data                   = $miraklOrder->getData();
                $data['status']         = $miraklOrder->getStatus() ? $miraklOrder->getStatus()->getState() : '';
                $data['shipping_price'] = $miraklOrder->getShipping()->getPrice(); // Excl. Tax
                $data['shipping_title'] = $miraklOrder->getShipping()->getType()->getLabel();
                $data['subtotal']       = $miraklOrder->getPrice(); // Excl. Tax

                // Add total tax amount
                $data['total_tax'] = $this->_helper->getMiraklOrderTaxAmount($miraklOrder, true);

                // Calculate grand total
                $data['grand_total'] = $miraklOrder->getTotalPrice() + $data['total_tax'];

                // Add Magento Order Id if found
                foreach ($magentoOrders as $magentoOrder) {
                    /** @var Mage_Sales_Model_Order $magentoOrder */
                    if ($magentoOrder->getMiraklOrderId() == $miraklOrder->getId()) {
                        $data['magento_order_id'] = $magentoOrder->getId();
                        $data['magento_increment_id'] = $magentoOrder->getIncrementId();
                        break;
                    }
                }

                $collection->addItem(new Varien_Object($data));
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->getLayout()->getMessagesBlock()->addError($e->getMessage());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'magento_order_id', array(
                'header'         => $this->__('Magento Order #'),
                'index'          => 'magento_order_id',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => array($this, 'decorateMagentoOrderId'),
                'align'          => 'center',
                'width'          => 1,
            )
        );

        $this->addColumn(
            'mirakl_order_id', array(
                'header'          => $this->__('Mirakl Order #'),
                'index'           => 'id',
                'filter_index'    => 'order_ids',
                'sortable'        => false,
                'html_decorators' => array('nobr'),
                'width'           => 1,
            )
        );

        $this->addColumn(
            'currency_iso_code', array(
                'header'          => $this->__('Currency'),
                'index'           => 'currency_iso_code',
                'filter'          => false,
                'sortable'        => false,
                'html_decorators' => array('nobr'),
            )
        );

        $orderStatuses = Mage::helper('mirakl_seller_sales')->getOrderStatusList();
        $this->addColumn(
            'status', array(
                'header'       => $this->__('Status'),
                'type'         => 'options',
                'index'        => 'status',
                'filter_index' => 'order_states',
                'sortable'     => false,
                'options'      => $orderStatuses,
            )
        );

        $this->addColumn(
            'order_lines', array(
                'header'   => $this->__('Order Lines'),
                'index'    => 'order_lines',
                'align'    => 'right',
                'filter'   => false,
                'sortable' => false,
                'getter'   => function ($row) {
                    return $row->getOrderLines() ? $row->getOrderLines()->count() : '';
                },
            )
        );

        $this->addColumn(
            'has_incident', array(
                'header'                => $this->__('Has Incident'),
                'index'                 => 'has_incident',
                'type'                  => 'options',
                'align'                 => 'center',
                'filter_index'          => 'has_incident',
                'filter_value_callback' => function ($value) {
                    return $value ? 'true' : 'false';
                },
                'sortable'     => false,
                'options'      => array(
                    1  => Mage::helper('adminhtml')->__('Yes'),
                    0  => Mage::helper('adminhtml')->__('No'),
                ),
            )
        );

        $this->addColumn(
            'shipping_title', array(
                'header'   => $this->__('Shipping Type'),
                'index'    => 'shipping_title',
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
            'total_tax', array(
                'header'   => $this->__('Total Tax Amount'),
                'index'    => 'total_tax',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'grand_total', array(
                'header'   => $this->__('Grand Total'),
                'index'    => 'grand_total',
                'type'     => 'currency',
                'currency' => 'currency_iso_code',
                'filter'   => false,
                'sortable' => false,
            )
        );

        $this->addColumn(
            'created_date', array(
                'header'   => $this->__('Created At'),
                'index'    => 'created_date',
                'filter'   => false,
                'sortable' => false,
                'getter'   => function ($row) {
                    return $row->getCreatedDate()
                        ->setTimezone(new \DateTimeZone('GMT'))
                        ->format('d/m/Y H:i:s');
                },
            )
        );

        $this->addColumn(
            'action', array(
                'header'   => Mage::helper('adminhtml')->__('Action'),
                'width'    => '50px',
                'align'    => 'center',
                'type'     => 'action',
                'getter'   => 'getId',
                'filter'   => false,
                'sortable' => false,
                'renderer' => 'mirakl_seller/adminhtml_widget_grid_column_renderer_action_links',
                'actions'  => array(
                    array(
                        'caption' => $this->__('View'),
                        'title'   => $this->__('View'),
                        'url'     => array('base' => '*/*/view', 'params' => array('connection_id' => $this->getConnection()->getId())),
                        'field'   => 'order_id',
                    ),
                    array(
                        'caption' => $this->__('Import'),
                        'title'   => $this->__('Import'),
                        'url'     => array('base' => '*/*/import', 'params' => array('connection_id' => $this->getConnection()->getId())),
                        'field'   => 'order_id',
                        'confirm' => $this->__('Are you sure you want to import this order in Magento?'),
                        'class'   => 'can-import',
                        'conds'   => function ($row) {
                            return $this->_helper->canImport($row->getStatus()) && !$row->getMagentoOrderId();
                        },
                    ),
                ),
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->setMassactionIdFilter('id');
        $this->getMassactionBlock()->setFormFieldName('mirakl_orders');

        $massImportUrl = $this->getUrl(
            '*/mirakl_seller_order/massImport', array('connection_id' => $this->getConnection()->getId())
        );

        $this->getMassactionBlock()->addItem(
            'import', array(
                'label'    => $this->__('Import'),
                'url'      => $massImportUrl,
                'selected' => false,
                'confirm'  => $this->__('You will import all selected Mirakl orders into Magento. Are you sure?'),
            )
        );

        return $this;
    }

    /**
     * @param   string          $magentoOrderId
     * @param   Varien_Object   $row
     * @return  string
     */
    public function decorateMagentoOrderId($magentoOrderId, $row)
    {
        if (!$magentoOrderId) {
            return '<em>' . $this->__('Not imported') . '</em>';
        }

        $url = $this->getUrl('*/sales_order/view', array('order_id' => $magentoOrderId));
        $magentoOrderId = sprintf('<a href="%s">%s</a>', $url, $row->getData('magento_increment_id'));

        return $magentoOrderId;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalJavaScript()
    {
        return <<<JS
            var massactionObject = {$this->getMassactionBlock()->getJsObjectName()};
            var massactionSelectId = '{$this->getMassactionBlock()->getHtmlId()}-select';
            \$(massactionSelectId).observe('change', function (event) {
                var massactionSelectedItem = massactionObject.getSelectedItem();
                if (massactionSelectedItem.id === 'import') {
                    // Verify that selected Mirakl orders can be imported into Magento
                    massactionObject.grid.rows.each(function (row) {
                        if (!row.select('.can-import').length) {
                            // Disable checkboxes of Mirakl orders that cannot be imported
                            row.select('.massaction-checkbox').each(function (checkbox) {
                                checkbox.checked = false;
                                checkbox.disabled = true;
                                massactionObject.setCheckbox(checkbox);
                            });
                        }
                    });
                } else {
                    // Remove 'disabled' flag from all checkboxes if action is not 'import'
                    massactionObject.getCheckboxes().each(function (checkbox) {
                        checkbox.disabled = false;
                    });
                }
            });
JS;
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/*/view', array(
                'order_id'      => $row->getId(),
                'connection_id' => $this->getConnection()->getId(),
            )
        );
    }
}
