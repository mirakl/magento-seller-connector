<?php
namespace Mirakl\Fixture\Catalog;

use Mirakl\Catalog\ProductLoaderTrait;
use Mirakl\Fixture\AbstractFixturesLoader;

class ProductsLoader extends AbstractFixturesLoader
{
    use ProductLoaderTrait;

    /**
     * {@inheritdoc}
     */
    public function load($file)
    {
        $productsData = $this->_getJsonFileContents($file);
        foreach ($productsData as $data) {
            if (!isset($data['sku'])) {
                throw new \InvalidArgumentException('Could not find "sku" field in product data');
            }

            $product = $this->loadProductBySku($data['sku']);

            $product->setOrigData(); // Set original data in order to compare values later
            $productEdited = false;

            if (isset($data['tier_price'])) {
                // Update tier price
                $tierPrices = $product->getTierPrice();
                $tierPriceData = $data['tier_price'];
                $tierPriceEdited = false;
                foreach ($tierPrices as $i => $tierPrice) {
                    $qty = strval($tierPrice['price_qty'] + 0);
                    if (isset($tierPriceData[$qty])) {
                        $tierPriceEdited = $tierPriceEdited|| $tierPriceData[$qty] != $tierPrice['price'];
                        $tierPrices[$i]['price'] = $tierPriceData[$qty];
                        $tierPrices[$i]['website_price'] = $tierPriceData[$qty];
                        unset($tierPriceData[$qty]);
                    } else {
                        unset($tierPrices[$i]);
                        $tierPriceEdited = true;
                    }
                }
                foreach ($tierPriceData as $qty => $price) {
                    $tierPrices[] = [
                        'website_id' => '0',
                        'all_groups' => '1',
                        'cust_group' => 32000,
                        'price' => $price,
                        'price_qty' => $qty,
                        'website_price' => $price,
                        'is_percent' => 0,
                    ];
                    $tierPriceEdited = true;
                }

                if ($tierPriceEdited) {
                    $product->setTierPrice($tierPrices);
                    $productEdited = true;
                }
                unset($data['tier_price']);
            }

            if (isset($data['configurable_attributes'])) {
                // Update configurable attributes
                $attributeModel = \Mage::getModel('eav/entity_attribute');
                $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributeCollection();
                $confAttrData = $data['configurable_attributes'];

                foreach ($configurableAttributes as $configurableAttribute) {
                    /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $configurableAttributes */
                    $attribute = $attributeModel->load($configurableAttribute->getAttributeId());
                    if (!$attribute) {
                        continue;
                    }

                    $attributeCode = $attribute->getAttributeCode();
                    if (isset($confAttrData[$attributeCode])) {
                        $edited = false;
                        $prices = $configurableAttribute->getPrices();
                        foreach ($prices as &$price) {
                            if (isset($confAttrData[$attributeCode][$price['default_label']])) {
                                $priceData = $confAttrData[$attributeCode][$price['default_label']];
                                $edited = $edited
                                    || $price['pricing_value'] != $priceData['pricing_value']
                                    || $price['is_percent'] != $priceData['is_percent'];

                                $price['pricing_value'] = $priceData['pricing_value'];
                                $price['is_percent'] = $priceData['is_percent'];
                            }
                        }

                        if ($edited) {
                            $configurableAttribute->setValues($prices);
                            $configurableAttribute->save();
                        }
                    }
                    // Implementation is needed to create new attribute configuration
                }
            }

            $product->addData($data);

            // Verify if data have changed in order to not save on each test execution for nothing
            foreach (array_keys($data) as $field) {
                if ($productEdited || $product->dataHasChangedFor($field)) {
                    $product->save();
                    break;
                }
            }
        }
    }
}