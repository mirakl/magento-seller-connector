<?php

class MiraklSeller_Core_Model_Observer_Listing
{
    /**
     * Call API AF01 and save result on listing's connection when displaying listing's form
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onPrepareListingForm(Varien_Event_Observer $observer)
    {
        /** @var MiraklSeller_Core_Model_Listing $listing */
        $listing = $observer->getListing();
        if (!$listing->getConnectionId()) {
            return;
        }

        try {
            $connection = $listing->getConnection();
            Mage::helper('mirakl_seller/connection')->updateOfferAdditionalFields($connection);
        } catch (\Exception $e) {
            Mage::logException($e);
            if (Mage::app()->getStore()->isAdmin()) {
                $message = Mage::helper('mirakl_seller')
                    ->__('Could not update offer additional fields: %s', $e->getMessage());
                /** @var Mage_Core_Model_Layout $layout */
                $layout = $observer->getBlock()->getLayout();
                $layout->getMessagesBlock()->addError($message);
            }
        }
    }
}