<?php

use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Block_Adminhtml_Connection_Edit_Tab_Export
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('export_settings', array('legend' => $this->__('Export Settings')));

        /** @var Connection $connection */
        $connection = $this->getConnection();
        $data = $connection->getData();

        $fieldset->addField(
            'magento_tier_prices_apply_on', 'select',
            array(
                'name'      => 'magento_tier_prices_apply_on',
                'label'     => $this->__('Magento Tier Prices Apply On'),
                'note'      => $this->__('Mirakl offers two variations to manage tier prices: "Volume pricing" and "Volume discounts". Marketplaces can choose to activate each of the variation. Depending on the marketplace configuration, choose how you would like Magento tier prices to be exported.'),
                'values'    => array(
                    Connection::VOLUME_PRICING   => $this->__('Volume Pricing'),
                    Connection::VOLUME_DISCOUNTS => $this->__('Volume Discounts'),
                ),
            )
        );

        $fieldset->addField(
            'exportable_attributes', 'multiselect',
            array(
                'name'      => 'exportable_attributes',
                'label'     => $this->__('Exportable Attributes (associated products)'),
                'note'      => $this->__('Select the attributes for which you want to export the values of the configurable product instead of the values of the associated product. Only applicable for products associated with a configurable product.'),
                'values'    => $this->exportableAttributeToOptionArray(),
            )
        );

        $fieldset->addField(
            'exported_prices_attribute', 'select',
            array(
                'name'      => 'exported_prices_attribute',
                'label'     => $this->__('Exported Prices Attribute'),
                'note'      => $this->__('By default exported prices are computed by the Magento pricing engine. To export a specific price for the current marketplace, you can create a product attribute with the type "price" and select it in this dropdown. In this scenario, discount prices cannot be exported. If the price attribute cannot be found or is empty for a specific product, the price exported will be the default price.'),
                'values'    => $this->exportedPricesAttributeToOptionArray(),
            )
        );

        $form->addValues($data);

        Mage::dispatchEvent(
            'mirakl_seller_prepare_connection_form_export', array(
                'connection' => $connection,
                'form'       => $form,
                'fieldset'   => $fieldset,
                'block'      => $this,
            )
        );

        $form->setUseContainer(false);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return $this->__('Export Settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('Export settings of this connection');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Retrieves all exportable attributes
     *
     * @return  array
     */
    public function exportableAttributeToOptionArray()
    {
        $attributes = new Varien_Object(array('collection' => null));

        Mage::dispatchEvent('mirakl_seller_request_exportable_attributes', array('attributes' => $attributes));

        $collection = $attributes->getData('collection');
        if ($collection === null) {
            $collection = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->setOrder('frontend_label', 'ASC');
        }

        return $this->_attributesToOptionArray($collection);
    }

    /**
     * Retrieves all attributes of type 'price'
     *
     * @return  array
     */
    public function exportedPricesAttributeToOptionArray()
    {
        $attributes = new Varien_Object(array('collection' => null));

        Mage::dispatchEvent('mirakl_seller_request_exported_prices_attribute', array('attributes' => $attributes));

        $collection = $attributes->getData('collection');
        if ($collection === null) {
            $collection = Mage::getResourceModel('catalog/product_attribute_collection')
                ->addVisibleFilter()
                ->addFieldToFilter('frontend_input', 'price')
                ->setOrder('frontend_label', 'ASC');
        }

        $options = $this->_attributesToOptionArray($collection);

        array_unshift($options, array(
            'value' => '',
            'label' => $this->__('-- Default Price --'),
        ));

        return $options;
    }

    /**
     * @param   Mage_Catalog_Model_Resource_Product_Attribute_Collection   $collection
     * @return  array
     */
    protected function _attributesToOptionArray(Mage_Catalog_Model_Resource_Product_Attribute_Collection $collection)
    {
        $options = array();
        foreach ($collection as $attribute) {
            /** @var Mage_Catalog_Model_Resource_Eav_Attribute $attribute */
            if ($attribute->getFrontendLabel()) {
                $options[] = array(
                    'value' => $attribute->getAttributeCode(),
                    'label' => sprintf('%s [%s]', $attribute->getFrontendLabel(), $attribute->getAttributeCode()),
                );
            }
        }

        return $options;
    }
}
