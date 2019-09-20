<?php

use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Block_Adminhtml_Connection_Edit_Tab_Operator
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

        $fieldset = $form->addFieldset('operator_report_config', array('legend' => $this->__('Error Report')));

        /** @var Connection $connection */
        $connection = $this->getConnection();
        $data = $connection->getData();

        $fieldset->addField(
            'sku_code', 'text',
            array(
                'name'  => 'sku_code',
                'label' => $this->__('SKU Column'),
                'note'  => $this->__(
                    'Column name containing the product SKU in the marketplace integration error report. ' .
                    'This is required to read the report and display integration errors messages at the product level in Magento.'
                ),
            )
        );

        $fieldset->addField(
            'errors_code', 'text',
            array(
                'name'  => 'errors_code',
                'label' => $this->__('Errors Column'),
                'note'  => $this->__('Name of the column containing errors messages in the integration files (API P44 only)'),
            )
        );

        if (empty($data['sku_code'])) {
            $data['sku_code'] = 'shop_sku';
        }

        if (empty($data['errors_code'])) {
            $data['errors_code'] = 'errors';
        }

        $fieldset = $form->addFieldset(
            'operator_report_success_config', array('legend' => $this->__('Success Report'))
        );

        $fieldset->addField(
            'success_sku_code', 'text',
            array(
                'name'  => 'success_sku_code',
                'label' => $this->__('SKU Column'),
                'note'  => $this->__(
                    'Column name containing the product SKU in the marketplace integration success report. ' .
                    'This is required to read the report and display integration success messages at the product level in Magento.'
                ),
            )
        );

        $fieldset->addField(
            'messages_code', 'text',
            array(
                'name'  => 'messages_code',
                'label' => $this->__('Messages Column'),
                'note'  => $this->__('Name of the column containing messages in the marketplace integration success report.'),
            )
        );

        if (empty($data['success_sku_code'])) {
            $data['success_sku_code'] = 'shop_sku';
        }

        if (empty($data['messages_code'])) {
            $data['messages_code'] = 'messages';
        }

        $form->addValues($data);

        Mage::dispatchEvent(
            'mirakl_seller_prepare_connection_form_operator', array(
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
        return $this->__('Marketplace Report Configuration');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('Configuration of the marketplace reports');
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
}
