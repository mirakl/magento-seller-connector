<?php

class MiraklSeller_Core_Model_Listing_Form_Builder_AdditionalFields
    implements MiraklSeller_Core_Model_Listing_Form_Builder_Interface
{
    const MAX_TEXT_SIZE     = '2000';
    const MAX_TEXTAREA_SIZE = '5000';

    /**
     * @var MiraklSeller_Core_Helper_Data
     */
    protected $_helper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_helper = Mage::helper('mirakl_seller');
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareForm(Varien_Data_Form_Element_Fieldset $fieldset, &$data, $params = array())
    {
        if (!isset($params['fields'])) {
            return;
        }

        foreach ($params['fields'] as $field) {
            $type = 'text';
            $config = array(
                'name'     => sprintf('additional_fields[defaults][%s]', $field['code']),
                'label'    => $field['label'],
                'title'    => $field['label'],
                'required' => $field['required'],
            );

            switch ($field['type']) {
                case 'BOOLEAN':
                    $type = 'select';
                    $values = array(
                        array(
                            'value' => 1,
                            'label' => $this->_helper->__('Yes'),
                        ),
                        array(
                            'value' => 0,
                            'label' => $this->_helper->__('No'),
                        ),
                    );
                    if (!$field['required']) {
                        array_unshift($values, $this->_getEmptyValue());
                    }

                    $config['values'] = $values;
                    break;

                case 'DATE':
                    $type = 'datetime';
                    $config['format'] = $this->_getDateTimeFormat();
                    $config['input_format'] = $this->_getDateTimeFormat();
                    $config['time'] = true;
                    $config['image'] = Mage::getDesign()->getSkinUrl('images/grid-cal.gif');
                    break;

                case 'TEXTAREA':
                    $type = 'textarea';
                    $config['maxlength'] = self::MAX_TEXTAREA_SIZE;
                    $config['note'] = $this->_helper->__('Maximum %s characters', self::MAX_TEXTAREA_SIZE);
                    break;

                case 'LIST':
                case 'MULTIPLE_VALUES_LIST':
                    $values = array();
                    foreach ($field['accepted_values'] as $value) {
                        $values[] = array(
                            'value' => $value,
                            'label' => $value,
                        );
                    }

                    if (!$field['required'] && $field['type'] == 'LIST') {
                        array_unshift($values, $this->_getEmptyValue());
                    }

                    $config['values'] = $values;
                    $type = $field['type'] == 'MULTIPLE_VALUES_LIST' ? 'multiselect' : 'select';
                    break;

                case 'REGEX':
                    if (!empty($field['regex'])) {
                        $config['note'] = $this->_helper->__(
                            'Must match the following format: %s', $this->_helper->escapeHtml($field['regex'])
                        );
                    }
                    break;

                case 'NUMERIC':
                    $type = 'text';
                    $config['class'] = 'validate-number';
                    $config['note'] = $this->_helper->__('Must be a valid number');
                    break;

                case 'LINK':
                    $type = 'text';
                    $config['class'] = 'validate-url';
                    $config['note'] = $this->_helper->__('Must be a valid URL');
                    break;

                case 'STRING':
                default:
                    $type = 'text';
                    $config['maxlength'] = self::MAX_TEXT_SIZE;
                    $config['note'] = $this->_helper->__('Maximum %s characters', self::MAX_TEXT_SIZE);
            }

            $attrValues = isset($data['offer_additional_fields_values']['attributes'])
                ? $data['offer_additional_fields_values']['attributes']
                : array();
            $attrElement = new Varien_Data_Form_Element_Select();
            $attrElement
                ->setId('attr-' . $field['code'])
                ->setName(sprintf('additional_fields[attributes][%s]', $field['code']))
                ->setForm($fieldset->getForm())
                ->setLabel('')
                ->setValue(isset($attrValues[$field['code']]) ? $attrValues[$field['code']] : '')
                ->setValues(Mage::getSingleton('mirakl_seller/system_config_source_attribute_dropdown')->toOptionArray());
            $config['additional_field_html'] = $attrElement->getHtml();

            $element = $fieldset->addField($field['code'], $type, $config);
            $element->setRenderer($this->_getElementRenderer());
        }
    }

    /**
     * @return  string
     */
    protected function _getDateTimeFormat()
    {
        return Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }

    /**
     * @return  MiraklSeller_Core_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Element
     */
    protected function _getElementRenderer()
    {
        /** @var MiraklSeller_Core_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Element $renderer */
        $renderer = Mage::getBlockSingleton('mirakl_seller/adminhtml_widget_form_renderer_fieldset_element');

        return $renderer;
    }

    /**
     * @return  array
     */
    protected function _getEmptyValue()
    {
        return array('value' => '', 'label' => Mage::helper('adminhtml')->__('-- Please Select --'));
    }
}