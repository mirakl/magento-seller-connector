<?php
namespace Mirakl\Aspect;

use AspectMock\Proxy\Verifier;
use AspectMock\Test;

trait AspectMockTrait
{
    /**
     * @param   string  $value
     * @return  Verifier
     */
    public static function mockBaseUrl($value = 'http://foobar.com/')
    {
        return Test::double(\Mage::class, ['getBaseUrl' => $value]);
    }

    /**
     * @param   string  $mockedPath
     * @param   mixed   $value
     * @return  Verifier
     */
    public static function mockConfigValue($mockedPath, $value)
    {
        return self::mockConfigValues([$mockedPath => $value]);
    }

    /**
     * @param   array   $mockedPaths
     * @return  Verifier
     */
    public static function mockConfigValues(array $mockedPaths)
    {
        return Test::double(\Mage::class, ['getStoreConfig' => function ($path) use ($mockedPaths) {
            return $mockedPaths[$path] ?? __AM_CONTINUE__;
        }]);
    }

    /**
     * @param   string  $modelClass
     * @param   mixed   $mockedModel
     * @return  Verifier
     */
    public static function mockModel($modelClass, $mockedModel)
    {
        return self::mockModels([$modelClass => $mockedModel]);
    }

    /**
     * @param   array   $mockedModels
     * @return  Verifier
     */
    public static function mockModels(array $mockedModels)
    {
        return Test::double(\Mage::class, ['getModel' => function ($modelClass) use ($mockedModels) {
            return $mockedModels[$modelClass] ?? __AM_CONTINUE__;
        }]);
    }
}