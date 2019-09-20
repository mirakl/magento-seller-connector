<?php

class MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Tab_Products_Wrapper
    extends Mage_Core_Block_Abstract
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        $listing = $this->getListing();
        if ($listing && $listing->getId()) {
            $collection = Mage::getModel('catalog/product')->getCollection();
            Mage::getResourceModel('mirakl_seller/offer')
                ->addOfferInfoToProductCollection($listing->getId(), $collection, '');
            $collection->addWebsiteFilter($listing->getWebsiteId());
            $this->_getGridBlock()->setProductsCount($collection->getSize());

            return $this->__('Products / Prices & Stocks (%s)', $collection->getSize());
        }

        return $this->__('Products / Prices & Stocks');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('List of associated products');
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
     * @return  MiraklSeller_Core_Model_Listing
     */
    public function getListing()
    {
        return Mage::registry('mirakl_seller_listing');
    }

    /**
     * @return  MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Tab_Products
     */
    protected function _getGridBlock()
    {
        return $this->getLayout()->getBlock('mirakl_listing_edit_tab_products');
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        $html = '';
        $listing = $this->getListing();

        if ($listing && $listing->getId()) {
            foreach ($this->getSortedChildren() as $name) {
                if ($block = $this->getLayout()->getBlock($name)) {
                    $html .= $block->toHtml();
                }
            }
        }

        return $html;
    }
}