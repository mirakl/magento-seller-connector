<?php

define('MAGENTO_ROOT', realpath(__DIR__ . '/../../../../'));

$compilerConfig = realpath(MAGENTO_ROOT . '/includes/config.php');
if (file_exists($compilerConfig)) {
    include $compilerConfig;
}

$kernel = \AspectMock\Kernel::getInstance();
$kernel->init([
    'debug' => true,
    'appDir' => MAGENTO_ROOT,
    'cacheDir' => MAGENTO_ROOT . '/var/tests/seller/cache/',
    'includePaths' => [MAGENTO_ROOT . '/app/', MAGENTO_ROOT . '/lib/'],
    'excludePaths' => [__DIR__, MAGENTO_ROOT . '/vendor/'],
]);

Mage::app('admin');

// Needed to use PHP SDK
Mage::getModel('mirakl_seller/autoload')->registerAutoload();

session_start();
