<?php

class MiraklSeller_Sales_Model_Mapper_Address implements MiraklSeller_Sales_Model_Mapper_Interface
{
    /**
     * {@inheritdoc}
     */
    public function map(array $data, $locale = null)
    {
        $countryId = $this->_getCountryResolver()->resolve($data, $locale);

        $phone = $data['phone'];
        if (!$phone && !empty($data['phone_secondary'])) {
            $phone = $data['phone_secondary'];
        }

        $result = array(
            'firstname'  => $data['firstname'],
            'lastname'   => $data['lastname'],
            'street'     => trim($data['street_1'] . "\n" . $data['street_2']),
            'telephone'  => $phone,
            'postcode'   => $data['zip_code'],
            'city'       => $data['city'],
            'country_id' => $countryId ?: '',
            'country'    => $data['country'],
        );

        return $result;
    }

    /**
     * @return  MiraklSeller_Sales_Model_Address_Country_Resolver
     */
    protected function _getCountryResolver()
    {
        return Mage::getSingleton('mirakl_seller_sales/address_country_resolver');
    }
}