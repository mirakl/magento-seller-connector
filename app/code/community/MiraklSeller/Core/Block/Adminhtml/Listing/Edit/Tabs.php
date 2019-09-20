<?php
/**
 * @method $this setTitle(string $title)
 */
class MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('listing_tabs');
        $this->setDestElementId('content');

        /** @var MiraklSeller_Core_Model_Listing $listing */
        $listing = Mage::registry('mirakl_seller_listing');

        if ($listing && $listing->getId()) {
            $this->setTitle($this->__('Listing #%s', $listing->getId()));
        } else {
            $this->setTitle($this->__('New Listing'));
        }
    }
}
