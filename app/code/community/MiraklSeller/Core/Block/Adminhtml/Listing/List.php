<?php

class MiraklSeller_Core_Block_Adminhtml_Listing_List extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize list
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'mirakl_seller';
        $this->_controller = 'adminhtml_listing';
        $this->_headerText = $this->__('Listing List');
    }

    /**
     * No class on header to remove the blank zone before the title
     *
     * @return  string
     */
    public function getHeaderCssClass()
    {
        return '';
    }
}
