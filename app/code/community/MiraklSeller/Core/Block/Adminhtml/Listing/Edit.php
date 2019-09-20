<?php

class MiraklSeller_Core_Block_Adminhtml_Listing_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'mirakl_seller';

    /**
     * @var string
     */
    protected $_controller = 'adminhtml_listing';

    /**
     * @var string
     */
    protected $_objectId = 'id';

    /**
     * Initialization
     */
    public function __construct()
    {
        parent::__construct();

        $model = $this->getListing();

        if ($model && $model->getId()) {
            $confirmText = $this->__("Are you sure you want to refresh this listing's products?");
            $this->addButton(
                'refresh', array(
                    'label'   => $this->__('1. Refresh Products'),
                    'title'   => $this->__("This action will refresh the listing's products"),
                    'class'   => 'scalable',
                    'onclick' => "confirmSetLocation('"
                        . Mage::helper('core')->jsQuoteEscape($confirmText)."', '{$this->getRefreshUrl()}')"
                )
            );

            $buttonData = array(
                'label'   => $this->__('2. Export Products'),
                'title'   => $this->__("This action will export the listing's products to Mirakl"),
                'class'   => 'scalable marketplace' . ($model->isActive() ? '' : ' disabled'),
                'onclick' => 'return miraklExport.exportDialog()',
            );
            if (!$model->isActive()) {
                $buttonData['disabled'] = 'disabled';
            }

            $this->addButton('export_product', $buttonData);

            $confirmText = $this->__("Are you sure you want to export prices & stocks for this listing?");
            $buttonData = array(
                'label'   => $this->__('3. Export Prices & Stocks'),
                'title'   => $this->__("Will export the listing's offers to Mirakl"),
                'class'   => 'scalable marketplace' . ($model->isActive() ? '' : ' disabled'),
                'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getExportOfferUrl()}')",
            );
            if (!$model->isActive()) {
                $buttonData['disabled'] = 'disabled';
            }

            $this->addButton('export_offer', $buttonData);

            $this->_updateButton('delete', 'label', $this->__('Delete Listing'));
            $this->_updateButton(
                'delete', 'onclick', sprintf(
                    "deleteConfirm('%s', '%s')",
                    $this->jsQuoteEscape($this->__('Are you sure you want to delete this Mirakl listing?')),
                    $this->getDeleteUrl()
                )
            );
        } elseif (!$this->getRequest()->getParam('connection', null)) {
            $this->removeButton('save');
        }
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
    public function getHeaderText()
    {
        $model = $this->getListing();
        if ($model && $model->getId()) {
            return $this->__("Edit Listing '%s'", $this->escapeHtml($model->getName()));
        }

        return $this->__('New Listing');
    }

    /**
     * @return  string
     */
    public function getHeaderCssClass()
    {
        return '';
    }

    /**
     * @return  string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save');
    }

    /**
     * @return  string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * @return  string
     */
    public function getDeleteUrl()
    {
        return $this->_getUrlWithId('*/*/delete');
    }

    /**
     * @return  string
     */
    public function getExportOfferUrl()
    {
        return $this->_getUrlWithId('*/*/exportOffer');
    }

    /**
     * @return  string
     */
    public function getRefreshUrl()
    {
        return $this->_getUrlWithId('*/*/refresh');
    }

    /**
     * @param   string  $route
     * @return  string
     */
    protected function _getUrlWithId($route)
    {
        return $this->getUrl($route, array($this->_objectId => $this->getRequest()->getParam($this->_objectId)));
    }
}
