<?php

class MiraklSeller_Process_Block_Adminhtml_Process_List extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize list
     */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'mirakl_seller_process';
        $this->_controller = 'adminhtml_process';
        $this->_headerText = $this->__('Process Report List');
        $this->_removeButton('add');

        // Add a Clear button that will delete all processes
        $confirm = $this->jsQuoteEscape($this->__('Are you sure? This will delete all existing processes.'));
        $url = $this->getUrl('*/*/clear', array('_current' => true));
        $this->addButton(
            'clear', array(
                'label'   => $this->__('Clear All'),
                'onclick' => "confirmSetLocation('$confirm', '$url')",
                'class'   => 'delete',
            )
        );
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
