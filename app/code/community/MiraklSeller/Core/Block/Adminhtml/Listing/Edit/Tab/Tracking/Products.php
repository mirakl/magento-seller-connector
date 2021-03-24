<?php

use MiraklSeller_Core_Model_Listing_Tracking_Status_Product as ProductStatus;

/**
 * @method MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection getCollection()
 */
class MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Tab_Tracking_Products
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * Initialize listing tracking grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('tracking_products');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('listing_tracking_product_filter');
        $this->_emptyText = $this->__('No product export tracking found for this listing');
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/trackingProductGrid', array('_current' => true));
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return $this->__('Track Products Exports');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('List of products exports');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return $this->getListing()->getId() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @param   string                                              $report
     * @param   MiraklSeller_Core_Model_Listing_Tracking_Product    $tracking
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column             $column
     * @return  string
     */
    public function decorateReport($report, $tracking, $column)
    {
        $html = '';

        if (strlen($report)) {
            $downloadUrl = $this->getUrl(
                '*/mirakl_seller_tracking_product/downloadReport', array(
                    'id' => $tracking->getId(),
                    'field' => $column->getId(),
                )
            );
            $html = sprintf(
                '<a href="%s" title="%s">%s</a>',
                $downloadUrl,
                $this->escapeHtml($this->__('Download report (CSV)')),
                $this->escapeHtml($this->__('Download'))
            );
        }

        return $html;
    }

    /**
     * @return  MiraklSeller_Core_Model_Listing
     */
    public function getListing()
    {
        return Mage::registry('mirakl_seller_listing');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        /** @var MiraklSeller_Core_Model_Resource_Listing_Tracking_Product_Collection $collection */
        $collection = Mage::getModel('mirakl_seller/listing_tracking_product')->getCollection();

        $fields = array(
            'id',
            'listing_id',
            'import_id',
            'import_status',
            'import_status_reason',
            'has_transformation_error_report' => new \Zend_Db_Expr('length(transformation_error_report) > 0'),
            'has_integration_error_report' => new \Zend_Db_Expr('length(integration_error_report) > 0'),
            'has_integration_success_report' => new \Zend_Db_Expr('length(integration_success_report) > 0'),
            'created_at',
            'updated_at',
        );
        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns($fields);

        $listing = $this->getListing();
        $listingId = $listing->getId() ?: 0;
        $collection->addListingFilter($listingId);
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', array(
                'header' => $this->__('Id'),
                'width'  => '50px',
                'index'  => 'id',
            )
        );

        $this->addColumn(
            'import_id', array(
                'header' => $this->__('Import Id'),
                'width'  => '50px',
                'index'  => 'import_id',
            )
        );

        $productImportStatuses = ProductStatus::getStatusLabels();
        array_walk($productImportStatuses, function (&$value) {
            $value = $this->__($value);
        });

        $this->addColumn(
            'import_status', array(
                'header'  => $this->__('Import Status'),
                'index'   => 'import_status',
                'type'    => 'options',
                'options' => $productImportStatuses,
            )
        );

        $this->addColumn(
            'import_status_reason', array(
                'header' => $this->__('Import Status Reason'),
                'index'  => 'import_status_reason',
            )
        );

        $this->addColumn(
            'transformation_error_report', array(
                'header'         => $this->__('Transformation<br>Error Report'),
                'index'          => 'has_transformation_error_report',
                'filter'         => false,
                'frame_callback' => array($this, 'decorateReport'),
            )
        );

        $this->addColumn(
            'integration_error_report', array(
                'header'         => $this->__('Integration<br>Error Report'),
                'index'          => 'has_integration_error_report',
                'filter'         => false,
                'frame_callback' => array($this, 'decorateReport'),
            )
        );

        $this->addColumn(
            'integration_success_report', array(
                'header'         => $this->__('Integration<br>Success Report'),
                'index'          => 'has_integration_success_report',
                'filter'         => false,
                'frame_callback' => array($this, 'decorateReport'),
            )
        );

        $this->addColumn(
            'updated_at', array(
                'header'          => $this->__('Updated At'),
                'width'           => '50px',
                'index'           => 'updated_at',
                'type'            => 'datetime',
                'html_decorators' => array('nobr'),
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
                        'caption' => $this->__('Update'),
                        'title'   => $this->__('Update'),
                        'url'     => array('base' => '*/mirakl_seller_tracking_product/update'),
                        'field'   => 'id',
                        'confirm' => $this->__('Are you sure you want to update this Mirakl products export tracking?'),
                        'conds'   => function ($tracking) {
                            /** @var MiraklSeller_Core_Model_Listing_Tracking_Product $tracking */
                            return !ProductStatus::isStatusFinal($tracking->getImportStatus());
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
        $this->getMassactionBlock()->setFormFieldName('tracking_products');

        $massUpdateUrl = $this->getUrl(
            '*/mirakl_seller_tracking_product/massUpdate', array('listing_id' => $this->getListing()->getId())
        );
        $this->getMassactionBlock()->addItem(
            'update', array(
                'label'    => $this->__('Update'),
                'url'      => $massUpdateUrl,
                'selected' => true,
                'confirm'  => Mage::helper('adminhtml')->__('Are you sure?'),
            )
        );

        $massDeleteUrl = $this->getUrl(
            '*/mirakl_seller_tracking_product/massDelete', array('listing_id' => $this->getListing()->getId())
        );
        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label'   => $this->__('Delete'),
                'url'     => $massDeleteUrl,
                'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
            )
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        $listing = $this->getListing();
        if (!$listing || !$listing->getId()) {
            return ''; // Hide grid for listing creation
        }

        return parent::_toHtml();
    }
}
