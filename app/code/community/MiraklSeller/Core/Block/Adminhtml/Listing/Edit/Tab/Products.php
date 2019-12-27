<?php

use MiraklSeller_Core_Model_Offer as Offer;

/**
 * @method Mage_Catalog_Model_Resource_Product_Collection getCollection()
 */
class MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Tab_Products extends Mage_Adminhtml_Block_Catalog_Product_Grid
{
    const MAX_PRODUCTS_FOR_SELECT_ALL = 20000;

    /**
     * @var int
     */
    protected $_productsCount = 0;

    /**
     * @var array
     */
    protected $_offerColumns = array(
        'product_id',
        'product_import_status',
        'product_import_id',
        'product_import_message',
        'offer_import_status',
        'offer_import_id',
        'offer_error_message',
    );

    /**
     * @var array
     */
    protected $_productStatusTooltips = array(
        Offer::PRODUCT_NEW                   => 'Will be sent automatically in the next export.',
        Offer::PRODUCT_PENDING               => 'Exported. Export status is about to be checked.',
        Offer::PRODUCT_TRANSFORMATION_ERROR  => 'Your data does not satisfy Mirakl validation. Check the error message. When your product is ready to be exported, click on the Export Products button.',
        Offer::PRODUCT_WAITING_INTEGRATION   => 'Marketplace integration in progress. Integration reports will be available soon in Magento.',
        Offer::PRODUCT_INTEGRATION_COMPLETE  => 'Waiting for successful Price & Stock export for final product availability in the marketplace.',
        Offer::PRODUCT_INTEGRATION_ERROR     => 'Your data does not satisfy the marketplace validation. Check the error message. When your product is ready to be exported, click on the Export Products button.',
        Offer::PRODUCT_INVALID_REPORT_FORMAT => 'The marketplace integration report file cannot be processed.',
        Offer::PRODUCT_NOT_FOUND_IN_REPORT   => 'Product not found in the marketplace integration reports.',
        Offer::PRODUCT_SUCCESS               => 'Product has been correctly imported in the marketplace.',
    );

    /**
     * @var array
     */
    protected $_offerStatusTooltips = array(
        Offer::OFFER_NEW     => 'Will be sent automatically in the next export.',
        Offer::OFFER_PENDING => 'Exported. Export status is about to be checked.',
        Offer::OFFER_SUCCESS => 'Price & Stock has been correctly imported in the marketplace.',
        Offer::OFFER_ERROR   => 'Your data does not satisfy Mirakl validation. Check the error message. When your price & stock is ready to be exported click on the Export Prices & Stocks button.',
        Offer::OFFER_DELETE  => 'Offer quantity will be set to zero in the marketplace during the next export.',
    );

    /**
     * Initialize listing product grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('products');
        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setVarNameFilter('listing_product_filter_' . $this->getListing()->getId());
        $this->_emptyText = $this->__('No product found for this listing');
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterLoadCollection()
    {
        /** @var MiraklSeller_Core_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('mirakl_seller/product_collection');
        $collection->addIdFilter($this->getCollection()->getLoadedIds());
        $collection->load();

        $collection->overrideByParentData($this->getListing(), array(), array(), true);
        $collection->addConfigurableAdditionalPrice();
        $parentData = $collection->getItems();

        /** @var Mage_Catalog_Model_Product $product */
        foreach ($this->getCollection() as $product) {
            if (isset($parentData[$product->getId()])) {
                $product->addData($parentData[$product->getId()]);

                if ($product->getAdditionalPrice()) {
                    $product->setPrice($product->getPrice() + $product->getAdditionalPrice());
                    $product->setFinalPrice($product->getFinalPrice() + $product->getAdditionalPrice());
                    $product->setSpecialPrice($product->getSpecialPrice() + $product->getAdditionalPrice());
                }
            }

            $data = Mage::getModel('mirakl_seller/listing_export_formatter_offer')->computePromotion(
                $product->getPrice(),
                $product->getFinalPrice(),
                $product->getSpecialPrice(),
                $product->getSpecialFromDate(),
                $product->getSpecialToDate()
            );

            $exportedPricesAttr = $this->getListing()->getConnection()->getExportedPricesAttribute();
            if ($exportedPricesAttr && !empty($product->getData($exportedPricesAttr))) {
                // If specific price field is set on the connection, use it and reset Magento calculated prices
                $product->setPrice($product->getData($exportedPricesAttr));
                $product->setFinalPrice($product->getData($exportedPricesAttr));
                $data['discount_price'] = '';
                $data['discount_start_date'] = '';
                $data['discount_end_date'] = '';
            }

            $product->addData($data);
        }

        return $this;
    }

    /**
     * @param   string  $value
     * @return  string
     */
    public function decorateMessage($value)
    {
        $shortValue = $value;
        if (strlen($value)) {
            $shortValue = Mage::helper('core/string')->truncate($value, 75);
        }

        return sprintf('<span title="%s">%s</span>', $this->escapeHtml($value), $shortValue);
    }

    /**
     * @param   string                                  $statusLabel
     * @param   Mage_Catalog_Model_Product              $row
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return  string
     */
    public function decorateStatus($statusLabel, $row, $column)
    {
        $key = $column->getData('index');
        $className = strtolower(str_replace('_', '-', $row->getData($key)));
        $tooltip = $key == 'product_import_status'
            ? $this->__($this->_productStatusTooltips[$row->getData($key)])
            : $this->__($this->_offerStatusTooltips[$row->getData($key)]);

        return sprintf(
            '<div class="tip"><span data-tip="%s" class="tooltip-seller status status-%s">%s</span></div>',
            $this->escapeHtml($tooltip),
            $className,
            $statusLabel
        );
    }

    /**
     * @param   Mage_Catalog_Model_Resource_Product_Collection  $collection
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column         $column
     */
    protected function _filterOfferCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }

        $collection->getSelect()
            ->where("{$column->getFilterIndex()} = ?", $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/productGrid', array('_current' => true));
    }

    /**
     * @return  MiraklSeller_Core_Model_Listing
     */
    public function getListing()
    {
        return Mage::registry('mirakl_seller_listing');
    }

    /**
     * @return  string
     */
    public function getClearAllButtonHtml()
    {
        return $this->getChildHtml('clear_all_button');
    }

    /**
     * @return  string
     */
    public function getClearAllButtonUrl()
    {
        return $this->getUrl('*/*/clearAll', array('id' => $this->getListing()->getId()));
    }

    /**
     * @return  string
     */
    public function getDownloadButtonHtml()
    {
        return $this->getChildHtml('download_button');
    }

    /**
     * @return  string
     */
    public function getDownloadButtonUrl()
    {
        return $this->getUrl('*/*/download', array('id' => $this->getListing()->getId()));
    }

    /**
     * {@inheritdoc}
     */
    public function getMainButtonsHtml()
    {
        $html = '';
        if ($this->getCollection()->getSize() > 0) {
            $html = $this->getDownloadButtonHtml() . $this->getClearAllButtonHtml();
        }

        return $html . parent::getMainButtonsHtml();
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            '*/catalog_product/edit', array(
                'store' => $this->getRequest()->getParam('store'),
                'id' => $row->getId(),
            )
        );
    }

    /**
     * Hide "Notify Low Stock RSS" link
     *
     * {@inheritdoc}
     */
    public function getRssLists()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        /** @var Mage_Adminhtml_Block_Widget_Grid_Column $actionCol */
        if ($actionCol = $this->_columns['action']) {
            $actionData = $actionCol->getData();
            $actionData['actions'][0]['url']['base'] = '*/catalog_product/edit';
            $actionCol->setData($actionData);
        }

        $this->addColumnAfter(
            'discount_price', array(
                'header'        => $this->__('Discount<br>Price'),
                'type'          => 'price',
                'filter'        => false,
                'sortable'      => false,
                'width'         => '50px',
                'currency_code' => $this->_getStore()->getBaseCurrency()->getCode(),
                'index'         => 'discount_price',
            ), 'price'
        );

        $this->addColumnAfter(
            'discount_start_date', array(
                'header'   => $this->__('Discount<br>Start Date'),
                'type'     => 'date',
                'filter'   => false,
                'sortable' => false,
                'width'    => '50px',
                'index'    => 'discount_start_date',
            ), 'discount_price'
        );

        $this->addColumnAfter(
            'discount_end_date', array(
                'header'   => $this->__('Discount<br>End Date'),
                'type'     => 'date',
                'filter'   => false,
                'sortable' => false,
                'width'    => '50px',
                'index'    => 'discount_end_date',
            ), 'discount_start_date'
        );

        $this->addColumnAfter(
            'product_id', array(
                'header'                    => $this->__('ID'),
                'type'                      => 'text',
                'width'                     => '50px',
                'index'                     => 'product_id',
                'filter_index'              => 'offers.product_id',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
            ), 'entity_id'
        );

        $this->addColumnAfter(
            'product_import_id', array(
                'header'                    => $this->__('Product<br>Import Id'),
                'type'                      => 'text',
                'width'                     => '80px',
                'index'                     => 'product_import_id',
                'filter_index'              => 'offers.product_import_id',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
            ), 'name'
        );

        $productStatusOptions = Offer::getProductStatusLabels();
        array_walk($productStatusOptions, function (&$value) {
            $value = $this->__($value);
        });

        $this->addColumnAfter(
            'product_import_status', array(
                'header'                    => $this->__('Product<br>Status'),
                'type'                      => 'options',
                'options'                   => $productStatusOptions,
                'index'                     => 'product_import_status',
                'filter_index'              => 'offers.product_import_status',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
                'frame_callback'            => array($this, 'decorateStatus'),
            ), 'product_import_id'
        );

        $this->addColumnAfter(
            'product_import_message', array(
                'header'                    => $this->__('Product<br>Import Message'),
                'type'                      => 'text',
                'index'                     => 'product_import_message',
                'filter_index'              => 'offers.product_import_message',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
                'frame_callback'            => array($this, 'decorateMessage'),
            ), 'product_import_status'
        );

        $this->addColumnAfter(
            'offer_import_id', array(
                'header'                    => $this->__('Prices & Stocks<br>Import Id'),
                'type'                      => 'text',
                'width'                     => '80px',
                'index'                     => 'offer_import_id',
                'filter_index'              => 'offers.offer_import_id',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
            ), 'product_import_message'
        );

        $offerStatusOptions = Offer::getOfferStatusLabels();
        array_walk($offerStatusOptions, function (&$value) {
            $value = $this->__($value);
        });

        $this->addColumnAfter(
            'offer_import_status', array(
                'header'                    => $this->__('Price & Stock<br>Status'),
                'type'                      => 'options',
                'options'                   => $offerStatusOptions,
                'index'                     => 'offer_import_status',
                'filter_index'              => 'offers.offer_import_status',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
                'frame_callback'            => array($this, 'decorateStatus'),
            ), 'offer_import_id'
        );

        $this->addColumnAfter(
            'offer_error_message', array(
                'header'                    => $this->__('Prices & Stocks<br>Import Message'),
                'type'                      => 'text',
                'index'                     => 'offer_error_message',
                'filter_index'              => 'offers.offer_error_message',
                'filter_condition_callback' => array($this, '_filterOfferCondition'),
                'frame_callback'            => array($this, 'decorateMessage'),
            ), 'offer_import_status'
        );

        $this->sortColumnsByOrder();

        // Remove entity_id here and not earlier in order to be able to define product_id column as the first one
        unset($this->_columns['entity_id']);

        return $this;
    }

    /**
     * Add the download button
     *
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        /** @var Mage_Adminhtml_Block_Widget_Button $button */

        // Add "Download Products" button
        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $buttonUrl = $this->getDownloadButtonUrl();
        $button->setData(
            array(
                'label'   => $this->__('Download Products for Mapping'),
                'title'   => $this->__("Download listing's products file and use it for the mapping in your Mirakl back office"),
                'class'   => 'scalable marketplace',
                'onclick' => "setLocation('$buttonUrl')",
            )
        );
        $this->setChild('download_button', $button);

        // Add "Clear All" button
        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $buttonUrl = $this->getClearAllButtonUrl();
        $confirmText = $this->jsQuoteEscape($this->escapeHtml($this->__('Are you sure you want to clear this Mirakl listing?')));
        $button->setData(
            array(
                'label'   => $this->__('Clear Listing Products'),
                'title'   => $this->__("Immediately clear all products associated with the listing"),
                'class'   => 'scalable delete',
                'onclick' => "confirmSetLocation('$confirmText', '$buttonUrl')",
            )
        );
        $this->setChild('clear_all_button', $button);

        return parent::_prepareLayout();
    }

    /**
     * Disable mass actions on this product grid
     *
     * {@inheritdoc}
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('products');

        if ($this->_productsCount >= self::MAX_PRODUCTS_FOR_SELECT_ALL) {
            // Disable 'Select All' feature if we have a large number of products because it slows down the page
            $this->getMassactionBlock()->setUseSelectAll(false);
        }

        $massNewUrl = $this->getUrl(
            '*/mirakl_seller_listing/massNewOffer', array('id' => $this->getListing()->getId())
        );
        $this->getMassactionBlock()->addItem(
            'new', array(
                'label'   => $this->__('Mark as Export'),
                'url'     => $massNewUrl,
                'confirm' => Mage::helper('adminhtml')->__('Are you sure?'),
            )
        );
    }

    /**
     * @param   Mage_Catalog_Model_Resource_Product_Collection  $collection
     */
    public function setCollection($collection)
    {
        $listing = $this->getListing();

        $collection->addAttributeToSelect(array('special_price', 'special_from_date', 'special_to_date'));

        if ($listing->getId()) {
            Mage::getResourceModel('mirakl_seller/offer')
                ->addOfferInfoToProductCollection($listing->getId(), $collection, $this->_offerColumns);

            Mage::helper('mirakl_seller/listing')->addListingPriceDataToCollection($listing, $collection, true);

            if ($exportedPricesAttr = $listing->getConnection()->getExportedPricesAttribute()) {
                $collection->addAttributeToSelect($exportedPricesAttr);
            }
        } else {
            $collection->addIdFilter(array(0)); // Workaround for empty collection
        }

        $this->_collection = $collection;
    }

    /**
     * @param   int $count
     * @return  $this
     */
    public function setProductsCount($count)
    {
        $this->_productsCount = (int) $count;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function _setCollectionOrder($column)
    {
        $collection = $this->getCollection();
        if ($collection && in_array($column->getId(), $this->_offerColumns)) {
            $collection->getSelect()
                ->order($column->getFilterIndex() . ' ' . $column->getDir());
        }

        parent::_setCollectionOrder($column);

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