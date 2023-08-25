<?php

use Mirakl\MMP\Shop\Domain\Order\ShopOrder as MiraklOrder;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Core_Helper_Connection extends Mage_Core_Helper_Data
{
    /**
     * @param   Connection  $connection
     * @return  MiraklSeller_Core_Model_Resource_Listing_Collection
     */
    public function getActiveListings(Connection $connection)
    {
        /** @var MiraklSeller_Core_Model_Resource_Listing_Collection $collection */
        $collection = Mage::getResourceModel('mirakl_seller/listing_collection')
            ->addConnectionFilter($connection)
            ->addActiveFilter()
            ->setOrder('name', 'ASC');

        return $collection;
    }

    /**
     * @param   Connection  $connection
     * @param   MiraklOrder $miraklOrder
     * @return  string
     */
    public function getMiraklOrderUrl(Connection $connection, MiraklOrder $miraklOrder)
    {
        $url = sprintf(
            '%s/mmp/shop/order/%s',
            $connection->getBaseUrl(),
            $miraklOrder->getId()
        );

        return $url;
    }

    /**
     * Calls API AF01 and updates offer additional fields of specified connection
     *
     * @param   Connection  $connection
     * @return  Connection
     */
    public function updateOfferAdditionalFields(Connection $connection)
    {
        $fields = Mage::helper('mirakl_seller_api/additionalField')->getOfferAdditionalFields($connection);
        $connection->setOfferAdditionalFields(json_encode($fields->toArray()));
        $connection->save();

        return $connection;
    }
}
