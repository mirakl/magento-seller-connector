<?php

use MiraklSeller_Core_Model_Listing as Listing;

/**
 * @method MiraklSeller_Core_Model_Resource_Listing_Collection getCollection()
 */
class MiraklSeller_Core_Block_Adminhtml_Listing_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * @var MiraklSeller_Core_Model_Resource_Offer
     */
    protected $_offerResource;

    /**
     * Initialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('listingGrid');
        $this->setDefaultSort('name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('listing_filter');
        $this->_offerResource = Mage::getResourceModel('mirakl_seller/offer');

    }

    /**
     * Define collection model for current grid
     *
     * @return  Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mirakl_seller/listing')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return  $this
     * @throws  Exception
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
            'connection_id', array(
                'header'  => $this->__('Connection'),
                'index'   => 'connection_id',
                'type'    => 'options',
                'options' => Mage::getModel('mirakl_seller_api/connection')->getCollection()->toOptionHash()
            )
        );

        $this->addColumn(
            'is_active', array(
                'header'         => $this->__('Active'),
                'index'          => 'is_active',
                'width'          => '80px',
                'type'           => 'options',
                'align'          => 'center',
                'frame_callback' => array($this, 'decorateStatus'),
                'options'        => array(
                    1  => Mage::helper('adminhtml')->__('Yes'),
                    0  => Mage::helper('adminhtml')->__('No'),
                ),
            )
        );

        $this->addColumn(
            'last_export_date', array(
                'header'          => $this->__('Last Export Date'),
                'align'           => 'right',
                'index'           => 'last_export_date',
                'width'           => 1,
                'type'            => 'datetime',
                'html_decorators' => array('nobr'),
            )
        );

        $this->addColumn(
            'nb_products', array(
                'header'          => $this->__('Products'),
                'align'           => 'right',
                'width'           => 1,
                'filter'          => false,
                'type'            => 'text',
                'html_decorators' => array('nobr'),
                'frame_callback'  => array($this, 'decorateNbProducts'),
            )
        );

        $this->addColumn(
            'exported_products', array(
                'header'          => $this->__('Exported'),
                'align'           => 'right',
                'width'           => 1,
                'filter'          => false,
                'type'            => 'text',
                'html_decorators' => array('nobr'),
                'frame_callback'  => array($this, 'decorateExportedProducts'),
            )
        );

        $this->addColumn(
            'products_errors', array(
                'header'          => $this->__('Errors'),
                'align'           => 'right',
                'filter'          => false,
                'width'           => 1,
                'type'            => 'text',
                'html_decorators' => array('nobr'),
                'frame_callback'  => array($this, 'decorateProductsWithErrors'),
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
                'renderer' => 'mirakl_seller/adminhtml_widget_grid_column_renderer_action_select',
                'actions'  => array(
                    array(
                        'caption' => $this->__('Edit'),
                        'title'   => $this->__('Edit'),
                        'url'     => array('base'  => '*/*/edit'),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => $this->__('Refresh Products'),
                        'title'   => $this->__("This action will refresh the listing's products"),
                        'url'     => array('base' => '*/*/refresh'),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
                    ),
                    array(
                        'caption' => $this->__('Download Products for Mapping'),
                        'title'   => $this->__("Download listing's products file and use it for the mapping in your Mirakl back office"),
                        'url'     => array('base' => '*/*/download'),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => $this->__('Export Pending Products'),
                        'title'   => $this->__("This action will export the listing's products to Mirakl"),
                        'url'     => array(
                            'base'   => '*/*/exportProduct',
                            'params' => array('export_mode' => strtolower(Listing::PRODUCT_MODE_PENDING))
                        ),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
                        'conds'   => array('is_active' => true),
                    ),
                    array(
                        'caption' => $this->__('Export Products in Error'),
                        'title'   => $this->__("This action will export the listing's products to Mirakl"),
                        'url'     => array(
                            'base'   => '*/*/exportProduct',
                            'params' => array('export_mode' => strtolower(Listing::PRODUCT_MODE_ERROR))
                        ),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
                        'conds'   => array('is_active' => true),
                    ),
                    array(
                        'caption' => $this->__('Export All Products'),
                        'title'   => $this->__("This action will export the listing's products to Mirakl"),
                        'url'     => array(
                            'base'   => '*/*/exportProduct',
                            'params' => array('export_mode' => strtolower(Listing::PRODUCT_MODE_ALL))
                        ),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
                        'conds'   => array('is_active' => true),
                    ),
                    array(
                        'caption' => $this->__('Export Prices & Stocks'),
                        'title'   => $this->__("This action will export the listing's prices & stocks to Mirakl"),
                        'url'     => array('base' => '*/*/exportOffer'),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
                        'conds'   => array('is_active' => true),
                    ),
                    array(
                        'caption' => $this->__('Delete'),
                        'title'   => $this->__('Delete'),
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
     * Filter store collection
     *
     * @param   MiraklSeller_Core_Model_Resource_Listing_Collection $collection
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column             $column
     */
    protected function _filterStoreCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $this->getCollection()->addStoreFilter($value);
    }

    /**
     * @param   mixed   $value
     * @param   Listing $row
     * @return  string
     */
    public function decorateStatus($value, $row)
    {
        $class = $row->getIsActive() ? 'grid-severity-notice' : 'grid-severity-critical';
        $value = $row->getIsActive() ? 'Yes' : 'No';

        return '<span class="' . $class . '"><span>' . $this->__($value) . '</span></span>';
    }

    /**
     * @param   mixed   $value
     * @param   Listing $row
     * @return  string
     */
    public function decorateNbProducts($value, $row)
    {
        return count($row->getProductIds());
    }

    /**
     * @param   mixed   $value
     * @param   Listing $row
     * @return  string
     */
    public function decorateExportedProducts($value, $row)
    {
        return $this->_offerResource->getNbListingSuccessProducts($row->getId());
    }

    /**
     * @param   mixed   $value
     * @param   Listing $row
     * @return  string
     */
    public function decorateProductsWithErrors($value, $row)
    {
        $nbErrors       = 0;
        $failedProducts = $this->_offerResource->getNbListingFailedProductsByStatus($row->getId());
        $failedOffers   = $this->_offerResource->getNbListingFailedOffers($row->getId());
        $productIds     = array();

        if (count($failedProducts)) {
            foreach ($failedProducts as $status => $failedProduct) {
                $nbErrors += $failedProduct['count'];
                $productIds += explode(',', $failedProduct['offer_product_id']);
            }

            if (!empty($failedOffers['count'])) {
                $offerProductIds = explode(',', $failedOffers['offer_product_id']);
                $nbProductsIntersect = count(array_intersect($productIds, $offerProductIds));
                $nbErrors = $nbErrors + $failedOffers['count'] - $nbProductsIntersect;
            }
        } elseif (!empty($failedOffers['count'])) {
            $nbErrors = $failedOffers['count'];
        }

        return $nbErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('_current' => true, 'id' => $row->getId()));
    }
}
