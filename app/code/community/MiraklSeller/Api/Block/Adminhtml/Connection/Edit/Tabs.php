<?php
/**
 * @method $this setTitle(string $title)
 */
class MiraklSeller_Api_Block_Adminhtml_Connection_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('connection_tabs');
        $this->setDestElementId('edit_form');

        /** @var MiraklSeller_Api_Model_Connection $connection */
        $connection = Mage::registry('mirakl_seller_connection');

        if ($connection && $connection->getId()) {
            $this->setTitle($this->__('Connection #%s', $connection->getId()));
        } else {
            $this->setTitle($this->__('New Connection'));
        }
    }
}
