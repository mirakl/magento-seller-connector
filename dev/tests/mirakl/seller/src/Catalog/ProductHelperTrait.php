<?php
namespace Mirakl\Catalog;

trait ProductHelperTrait
{
    /**
     * @return  int
     */
    public function getLastProductId()
    {
        /** @var \Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = \Mage::getModel('catalog/product')->getCollection();
        $collection->getSelect()
            ->reset('columns')
            ->columns(new \Zend_Db_Expr('MAX(e.entity_id)'));

        return $collection->getConnection()->fetchOne($collection->getSelect());
    }
}