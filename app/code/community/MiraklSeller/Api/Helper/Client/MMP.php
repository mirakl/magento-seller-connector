<?php

use Mirakl\MMP\Common\Domain\Collection\Locale\LocaleCollection;
use Mirakl\MMP\Common\Request\Locale\GetLocalesRequest;
use MiraklSeller_Api_Model_Connection as Connection;

/**
 * @method \Mirakl\MMP\Shop\Client\ShopApiClient getClient(Connection $connection)
 */
class MiraklSeller_Api_Helper_Client_MMP extends MiraklSeller_Api_Helper_Client_Abstract
{
    const AREA_NAME = 'MMP';

    /**
     * @var array
     */
    protected $_activeLocales;

    /**
     * (L01) Get active locales in Mirakl platform
     *
     * @param   Connection  $connection
     * @return  LocaleCollection
     */
    public function getActiveLocales(Connection $connection)
    {
        if (null === $this->_activeLocales) {
            $this->_activeLocales = $this->send($connection, new GetLocalesRequest());
        }

        return $this->_activeLocales;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getArea()
    {
        return self::AREA_NAME;
    }
    
    /**
     * Verify that specified locale exists in Mirakl. If not, reset it.
     *
     * @param   Connection  $connection
     * @param   string      $locale
     * @return  null|string
     */
    protected function _validateLocale(Connection $connection, $locale)
    {
        try {
            $locales = $this->getActiveLocales($connection)->walk('getCode');
        } catch (\Exception $e) {
            Mage::logException($e);
            return null;
        }

        return in_array($locale, $locales) ? $locale : null;
    }
}