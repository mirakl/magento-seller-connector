<?php

class MiraklSeller_Core_Model_Observer_Connection
{
    /**
     * Return only attribute use for product export
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onRequestExportableAttributes(Varien_Event_Observer $observer)
    {
        $observer->getAttributes()->setData(
            'collection',
            Mage::getResourceSingleton('mirakl_seller/product')->getExportableAttributes()
        );
    }
}