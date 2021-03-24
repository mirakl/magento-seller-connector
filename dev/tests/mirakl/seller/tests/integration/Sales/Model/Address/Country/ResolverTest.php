<?php
namespace Mirakl\Test\Integration\Sales\Model\Address;

use Mirakl\Test\Integration\Sales;

/**
 * @group sales
 * @group model
 * @group address
 * @coversDefaultClass \MiraklSeller_Sales_Model_Address_Country_Resolver
 */
class ResolverTest extends Sales\TestCase
{
    /**
     * @param   array           $data
     * @param   string|null     $locale
     * @param   string          $defaultLocale
     * @param   string|false    $expected
     * @dataProvider getTestResolveDataProvider
     */
    public function testResolve(array $data, $locale, $defaultLocale, $expected)
    {
        /** @var \MiraklSeller_Sales_Model_Address_Country_Resolver $countryResolver */
        $countryResolver = \Mage::getSingleton('mirakl_seller_sales/address_country_resolver');
        $countryResolver->setDefaultLocale($defaultLocale);

        $countryId = $countryResolver->resolve($data, $locale);

        $this->assertSame($expected, $countryId);
    }

    /**
     * @return  array
     */
    public function getTestResolveDataProvider()
    {
        return [
            [['country_iso_code' => 'FRA', 'country' => 'foo'], 'fr_FR', 'en_US', 'FR'],
            [['country_iso_code' => 'GBR', 'country' => 'Royaume-Uni'], 'fr_FR', 'en_US', 'GB'],
            [['country' => 'Royaume-Uni'], 'fr_FR', 'en_US', 'GB'],
            [['country' => 'United Kingdom'], 'fr_FR', 'en_US', 'GB'],
            [['country' => ''], 'en_US', 'en_US', false],
            [['country' => 'foobar'], 'en_GB', 'en_GB', false],
        ];
    }
}