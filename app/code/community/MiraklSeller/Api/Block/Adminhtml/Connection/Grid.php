<?php
/**
 * @method MiraklSeller_Api_Model_Resource_Connection_Collection getCollection()
 */
class MiraklSeller_Api_Block_Adminhtml_Connection_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('connectionGrid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('connection_filter');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mirakl_seller_api/connection')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'name', array(
                'header' => $this->__('Name'),
                'index'  => 'name',
                'type'   => 'text',
                'escape' => true,
            )
        );

        $this->addColumn(
            'api_url', array(
                'header' => $this->__('API URL'),
                'index'  => 'api_url',
                'type'   => 'text',
            )
        );

        $this->addColumn(
            'api_key', array(
                'header' => $this->__('API Key'),
                'index'  => 'api_key',
                'type'   => 'text',
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn(
                'store_id', array(
                    'header'                    => $this->__('Store View'),
                    'index'                     => 'store_id',
                    'type'                      => 'store',
                    'store_all'                 => true,
                    'store_view'                => true,
                    'sortable'                  => true,
                    'skipEmptyStoresLabel'      => true,
                    'filter_condition_callback' => array($this, '_filterStoreCondition'),
                )
            );
        }

        $this->addColumn(
            'shop_id', array(
                'header' => $this->__('Shop Id'),
                'index'  => 'shop_id',
                'type'   => 'text',
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
                'actions'  => array(
                    array(
                        'caption' => $this->__('Edit'),
                        'url'     => array('base' => '*/*/edit'),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => $this->__('Test'),
                        'url'     => array('base' => '*/*/test'),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => $this->__('Delete'),
                        'url'     => array('base' => '*/*/delete'),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?')
                    ),
                ),
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Filter connection collection
     *
     * @param   MiraklSeller_Api_Model_Resource_Connection_Collection   $collection
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column                 $column
     */
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $collection->addStoreFilter($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('_current' => true, 'id' => $row->getId()));
    }
}
