<?php
namespace Mirakl\App;

trait EnvEmulationTrait
{
    /**
     * @var \Mage_Core_Model_App_Emulation
     */
    static protected $_appEmulation;

    /**
     * @var \Varien_Object
     */
    static protected $_initialEnvInfo;

    /**
     * @param   mixed   $store
     * @return  void
     */
    public static function startEnvEmulation($store)
    {
        self::$_initialEnvInfo = self::getAppEmulation()->startEnvironmentEmulation($store);
    }

    /**
     * @return  void
     */
    public static function stopEnvEmulation()
    {
        self::getAppEmulation()->stopEnvironmentEmulation(self::$_initialEnvInfo);
    }

    /**
     * @return  \Mage_Core_Model_App_Emulation
     */
    public static function getAppEmulation()
    {
        return \Mage::getSingleton('core/app_emulation');
    }
}