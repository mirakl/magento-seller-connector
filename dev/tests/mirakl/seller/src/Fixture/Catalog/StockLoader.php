<?php
namespace Mirakl\Fixture\Catalog;

use Mirakl\Fixture\AbstractFixturesLoader;

class StockLoader extends AbstractFixturesLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($file)
    {
        $stockData = $this->_getJsonFileContents($file);
        foreach ($stockData as $sku => $data) {
            $productId = \Mage::getResourceSingleton('catalog/product')->getIdBySku($sku);
            /** @var \Mage_CatalogInventory_Model_Stock_Item $stockItem */
            $stockItem = \Mage::getModel('cataloginventory/stock_item');
            $stockItem->getResource()->loadByProductId($stockItem, $productId);

            if (!$stockItem->getId()) {
                continue;
            }

            $stockItem->setOrigData(); // Set original data in order to compare values later
            $stockItem->addData($data);

            // Verify if data have changed in order to not save on each test execution for nothing
            foreach (array_keys($data) as $field) {
                if ($stockItem->dataHasChangedFor($field)) {
                    $stockItem->save();
                    break;
                }
            }
        }
    }
}