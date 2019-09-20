<?php

require 'vendor/autoload.php';

use Mirakl\App;
use Mirakl\Fixture;

define('MAGENTO_ROOT', realpath(__DIR__ . '/../../../../'));

$mageFilename = realpath(MAGENTO_ROOT . '/app/Mage.php');
if (!file_exists($mageFilename)) {
    exit($mageFilename . " was not found");
}

require $mageFilename;

// Bootstrap Magento
Mage::app('admin');

// Needed to use PHP SDK
Mage::getModel('mirakl_seller/autoload')->registerAutoload();

$appState = new App\State();

(new Fixture\System\ConfigLoader($appState))->load('fixtures/config.json');
(new Fixture\Catalog\ProductsLoader($appState))->load('fixtures/products.json');
(new Fixture\Catalog\StockLoader($appState))->load('fixtures/stock.json');

if ($appState->getNeedsFullReindex()) {
    /** @var \Mage_Index_Model_Indexer $indexer */
    $indexer = \Mage::getModel('index/indexer');
    $processes = $indexer->getProcessesCollection();
    $processes->walk('reindexAll');
}

if ($appState->getNeedsConfigReinit()) {
    Mage::getConfig()->reinit();
}

if ($appState->getNeedsCacheClearing()) {
    Mage::app()->cleanCache();
}
