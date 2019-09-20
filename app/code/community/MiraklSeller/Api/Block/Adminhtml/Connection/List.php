<?php

class MiraklSeller_Api_Block_Adminhtml_Connection_List extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize list
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'mirakl_seller_api';
        $this->_controller = 'adminhtml_connection';
        $this->_headerText = $this->__('Connection List');
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
