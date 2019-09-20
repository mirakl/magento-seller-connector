<?php

class MiraklSeller_Sales_Model_Address_Country_Resolver
{
    /**
     * @var string
     */
    protected $defaultLocale = 'en_US';

    /**
     * @return  string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @param   string  $locale
     * @return  $this
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = (string) $locale;

        return $this;
    }

    /**
     * @param   string  $countryIso3Code
     * @return  string|false
     */
    protected function _getCountryIdByIso3Code($countryIso3Code)
    {
        /** @var Mage_Directory_Model_Resource_Region_Collection $collection */
        $collection = Mage::getResourceModel('directory/region_collection');
        $collection->addCountryCodeFilter($countryIso3Code);

        /** @var Mage_Directory_Model_Region $region */
        $region = $collection->getFirstItem();

        return $region->getCountryId() ?: false;
    }

    /**
     * @param   string      $countryLabel
     * @param   string|null $locale
     * @return  string|false
     */
    protected function _getCountryIdByLabel($countryLabel, $locale = null)
    {
        $countries = Mage::helper('mirakl_seller_sales/order')->getCountryList($locale);

        return array_search($countryLabel, $countries);
    }

    /**
     * @param   array       $data
     * @param   string|null $locale
     * @return  string|false
     */
    public function resolve(array $data, $locale = null)
    {
        $countryId = false;

        if (!empty($data['country_iso_code'])) {
            // Try with country ISO 3 code
            $countryId = $this->_getCountryIdByIso3Code($data['country_iso_code']);
        }

        if (false === $countryId && !empty($data['country'])) {
            if (null !== $locale) {
                // Try with specified locale
                $countryId = $this->_getCountryIdByLabel($data['country'], $locale);
            }

            if (false === $countryId && $locale !== $this->defaultLocale) {
                // Try with default locale
                $countryId = $this->_getCountryIdByLabel($data['country'], $this->defaultLocale);
            }
        }

        return $countryId;
    }
}