<?php

class MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Tab_Main
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $this->setChild(
            'continue_button',
            $this->getLayout()->createBlock('adminhtml/widget_button')->setData(
                array(
                    'label'     => Mage::helper('catalog')->__('Continue'),
                    'onclick'   => "editForm.submit();",
                    'class'     => 'save'
                )
            )
        );

        return parent::_prepareLayout();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id'     => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post'
            )
        );

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => $this->__('Listing Information')));

        /** @var MiraklSeller_Core_Model_Listing $listing */
        $listing = Mage::registry('mirakl_seller_listing');
        $data = $listing->getData();

        $connectionId = $listing->getConnectionId();
        if (!$connectionId) {
            if ($connectionId = $this->getRequest()->getParam('connection', null)) {
                $data['connection_id'] = $connectionId;
                $listing->setConnectionId($connectionId);
            }
        }

        Mage::dispatchEvent(
            'mirakl_seller_prepare_listing_form_init', array(
                'block'   => $this,
                'listing' => $listing,
            )
        );

        if ($connectionId) {
            if ($listing->getId()) {
                $fieldset->addField('id', 'hidden', array('name' => 'id'));
            }

            $fieldset->addField('connection_id', 'hidden', array('name' => 'connection_id'));

            $fieldset->addField(
                'name', 'text', array(
                    'name'     => 'name',
                    'label'    => $this->__('Name'),
                    'class'    => 'required-entry',
                    'required' => true,
                )
            );

            $fieldset->addField(
                'is_active', 'select', array(
                    'name'   => 'is_active',
                    'label'  => $this->__('Is Active'),
                    'note'   => $this->__('If inactive, listing will not be exported.'),
                    'values' => array(
                        '1' => Mage::helper('adminhtml')->__('Yes'),
                        '0' => Mage::helper('adminhtml')->__('No'),
                    ),
                )
            );

            if (!isset($data['is_active'])) {
                $data['is_active'] = 1; // Default value
            }

            $data['connection_id_view'] = $data['connection_id'];
            $fieldset->addField(
                'connection_id_view', 'select', array(
                    'name'     => 'connection_id_view',
                    'label'    => $this->__('Connection'),
                    'title'    => $this->__('Connection'),
                    'values'   => Mage::getModel('mirakl_seller_api/connection')->getCollection()->toOptionArray(),
                    'disabled' => true,
                )
            );

            $fieldset->addField(
                'offer_state', 'select', array(
                    'name'     => 'offer_state',
                    'label'    => $this->__('Products Condition'),
                    'title'    => $this->__('Products Condition'),
                    'values'   => Mage::getModel('mirakl_seller/offer_state')->toOptionArray(),
                    'note'     => $this->__('Specify the state to use when exporting prices & stocks of the listing.'),
                )
            );

            if (!isset($data['offer_state']) || empty($data['offer_state'])) {
                $data['offer_state'] = MiraklSeller_Core_Model_Offer_State::DEFAULT_STATE;
            }

            $fieldset->addField('builder_model', 'hidden', array('name' => 'builder_model'));

            if (!isset($data['builder_model'])) {
                $data['builder_model'] = MiraklSeller_Core_Model_Listing::DEFAULT_BUILDER_MODEL; // Default value
            }

            if (isset($data['builder_params'])) {
                foreach ($data['builder_params'] as $key => $value) {
                    $data[$key] = $value;
                }
            }

            // Add custom fields from listing builder
            $builder = $listing->getBuilder();
            $builder->prepareForm($form, $data);

            // Add additional fields
            $offerAdditionalFields = $listing->getOfferAdditionalFields();
            $fieldsetAdditionalFields = $form->addFieldset(
                'offer_additional_fields_fieldset', array(
                    'legend'  => $this->__('Additional Fields'),
                    'comment' => !$offerAdditionalFields ? $this->__('There is no additional field to configure.') : '',
                )
            );
            $fieldsetAdditionalFields->setRenderer(
                $this->getLayout()->createBlock('mirakl_seller/adminhtml_widget_form_renderer_additionalFieldsFieldset')
            );

            if ($offerAdditionalFields) {
                $additionalFieldsFormBuilder = Mage::getModel('mirakl_seller/listing_form_builder_additionalFields');
                $additionalFieldsFormBuilder->prepareForm($fieldsetAdditionalFields, $data, array('fields' => $offerAdditionalFields));
            }

            if (isset($data['offer_additional_fields_values']['defaults'])) {
                // Initialize additional fields default values
                $data = array_merge($data, $data['offer_additional_fields_values']['defaults']);
            }

            // Add reference ifentifier field
            $fieldsetIdentifiers = $form->addFieldset(
                'identifiers_fieldset', array('legend' => $this->__('Product Reference Identifiers'))
            );

            $fieldsetIdentifiers->addField(
                'product_id_type', 'text', array(
                    'name'  => 'product_id_type',
                    'label' => $this->__('Product Id Type'),
                    'note'  => $this->__('This code will be used to fill the "product_id_type" field in the prices & stocks export file.'),
                )
            );

            if (!isset($data['product_id_type'])) {
                $data['product_id_type'] = MiraklSeller_Core_Model_Listing_Export_Formatter_Offer::DEFAULT_PRODUCT_ID_TYPE;
            }

            $fieldsetIdentifiers->addField(
                'product_id_value_attribute', 'select', array(
                'name'   => 'product_id_value_attribute',
                'label'  => $this->__('Product Id Value'),
                'values' => $this->attributeToOptionArray(),
                'note'   => $this->__('The selected attribute will be used as the reference value to identify a ' .
                    'product in Mirakl. Only attributes defined in the global scope are listed here.'),
                )
            );

            if (!isset($data['product_id_value_attribute'])) {
                $data['product_id_value_attribute'] = 'sku';
            }

            // Add custom field for variants
            $fieldsetVariants = $form->addFieldset(
                'variants_fieldset', array('legend' => $this->__('Configurable Attributes'))
            );

            $fieldsetVariants->addField(
                'variants_attributes', 'multiselect', array(
                    'name'   => 'variants_attributes',
                    'label'  => $this->__('Skip Configurable Attributes'),
                    'values' => $this->variantAttributeToOptionArray(),
                    'note'   => $this->__('Select the configurable attributes not supported by the marketplace, if any. ' .
                        'Refer to the documentation for more information.'),
                )
            );

            Mage::dispatchEvent(
                'mirakl_seller_prepare_listing_form', array(
                    'block'   => $this,
                    'listing' => $listing,
                    'form'    => $form,
                )
            );

            $form->addValues($data);
            $form->setAction($this->getUrl('*/*/save'));

            Mage::dispatchEvent(
                'mirakl_seller_prepare_listing_form_fieldsets', array(
                    'block'                      => $this,
                    'listing'                    => $listing,
                    'fieldset'                   => $fieldset,
                    'fieldset_identifiers'       => $fieldsetIdentifiers,
                    'fieldset_variants'          => $fieldsetVariants,
                    'fieldset_additional_fields' => $fieldsetAdditionalFields,
                )
            );
        } else {
            $fieldset->addField(
                'connection_id', 'select', array(
                'name'   => 'connection_id',
                'label'  => $this->__('Connection'),
                'title'  => $this->__('Connection'),
                'values' => Mage::getModel('mirakl_seller_api/connection')->getCollection()->toOptionArray(),
                'note'   => $this->__('The Mirakl platform to which the listing products will be exported.'),
                )
            );

            $fieldset->addField(
                'continue_button', 'note', array('text' => $this->getChildHtml('continue_button'))
            );

            $form->addValues($data);
            $form->setAction($this->getUrl('*/*/connection'));
            $form->setUseContainer(true);
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return $this->__('Listing Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('Information about the listing');
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
     * Retrieves all product attributes defined in global scope in order to choose a potential mapping.
     *
     * @return  array
     */
    public function attributeToOptionArray()
    {
        $options = array(
            array(
                'value' => '',
                'label' => $this->__('-- Please Select --'),
            )
        );

        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->addFieldToFilter('is_global', '1')
            ->setOrder('frontend_label', 'ASC');

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

    /**
     * Retrieves all product attributes with type Dropdown or Yes/No, use as configurable attribute
     * and in global scope in order to choose a potential variant axis.
     *
     * @return  array
     */
    public function variantAttributeToOptionArray()
    {
        $options = array();

        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->addFieldToFilter('frontend_input', 'select')
            ->addFieldToFilter('is_configurable', '1')
            ->addFieldToFilter('is_global', '1')
            ->setOrder('frontend_label', 'ASC');

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
