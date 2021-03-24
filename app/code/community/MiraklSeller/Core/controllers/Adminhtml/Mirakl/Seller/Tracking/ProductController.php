<?php

require_once 'MiraklSeller/Core/controllers/Adminhtml/Mirakl/Seller/Tracking/AbstractController.php';

class MiraklSeller_Core_Adminhtml_Mirakl_Seller_Tracking_ProductController
    extends MiraklSeller_Core_Adminhtml_Mirakl_Seller_Tracking_AbstractController
{
    /**
     * {@inheritdoc}
     */
    protected function _getModelClass()
    {
        return 'mirakl_seller/listing_tracking_product';
    }

    /**
     * {@inheritdoc}
     */
    protected function _getTrackingType()
    {
        return MiraklSeller_Core_Model_Listing::TYPE_PRODUCT;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getActiveTab()
    {
        return 'tracking_products';
    }
}
