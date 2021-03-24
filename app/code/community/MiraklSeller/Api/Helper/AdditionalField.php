<?php

use Mirakl\MMP\Common\Domain\Collection\AdditionalFieldCollection;
use Mirakl\MMP\Shop\Request\AdditionalField\GetAdditionalFieldRequest;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Helper_AdditionalField extends MiraklSeller_Api_Helper_Client_MMP
{
    /**
     * (AF01) Get the list of any additional fields
     *
     * @param   Connection  $connection
     * @param   array       $entities   For example: ['OFFER', 'SHOP']
     * @param   string      $locale
     * @return  AdditionalFieldCollection
     */
    public function getAdditionalFields(Connection $connection, $entities, $locale = null)
    {
        $request = new GetAdditionalFieldRequest();
        $request->setEntities($entities);
        $request->setLocale($this->_validateLocale($connection, $locale));

        Mage::dispatchEvent('mirakl_seller_api_additional_fields_before', array('request' => $request));

        return $this->send($connection, $request);
    }

    /**
     * @param   Connection  $connection
     * @param   string      $locale
     * @return  AdditionalFieldCollection
     */
    public function getOfferAdditionalFields(Connection $connection, $locale = null)
    {
        return $this->getAdditionalFields($connection, array('OFFER'), $locale);
    }
}
