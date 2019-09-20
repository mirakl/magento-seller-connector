<?php
/**
 * @method \Mirakl\MCI\Shop\Client\ShopApiClient getClient(MiraklSeller_Api_Model_Connection $connection)
 */
class MiraklSeller_Api_Helper_Client_MCI extends MiraklSeller_Api_Helper_Client_Abstract
{
    const AREA_NAME = 'MCI';

    /**
     * {@inheritdoc}
     */
    protected function _getArea()
    {
        return self::AREA_NAME;
    }
}