<?php

class MiraklSeller_Api_Block_Adminhtml_Connection_Edit_Tab_Main
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
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

        $fieldset = $form->addFieldset(
            'base_fieldset', array('legend' => $this->__('Connection Information'))
        );

        /** @var MiraklSeller_Api_Model_Connection $connection */
        $connection = Mage::registry('mirakl_seller_connection');
        $data = $connection->getData();

        if ($connection->getId()) {
            $fieldset->addField('id', 'hidden', array('name' => 'id'));
        }

        $fieldset->addField(
            'name', 'text',
            array(
                'name'     => 'name',
                'label'    => $this->__('Name'),
                'class'    => 'required-entry',
                'required' => true,
            )
        );

        $tooltipImage = $this->getSkinUrl('images/mirakl_seller/mirakl_api_connection_url.png');
        $fieldset->addField(
            'api_url', 'text',
            array(
                'name'               => 'api_url',
                'label'              => $this->__('API URL'),
                'after_element_html' => '<div class="field-tooltip"><div><img src="' . $this->escapeUrl($tooltipImage) . '" /></div></div>',
                'class'              => 'required-entry validate-api-url',
                'required'           => true,
                'note'               => $this->__(
                    'For example: https://&lt;your_mirakl&gt;/api<br>' .
                    'Replace &lt;your_mirakl&gt; with the URL you are using to log in to your Mirakl back office for this connection.<br>' .
                    'This URL should have been provided by the marketplace.'
                ),
            )
        );

        $fieldset->addField(
            'api_key', 'text',
            array(
                'name'     => 'api_key',
                'label'    => $this->__('API Key'),
                'note'     => $this->__('A shop API key looks like this:<br>xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxx'),
                'class'    => 'required-entry',
                'required' => true,
            )
        );

        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField(
                'store_id', 'select', array(
                    'name'   => 'store_id',
                    'label'  => $this->__('Store View'),
                    'title'  => $this->__('Store View'),
                    'class'  => 'required-entry',
                    'values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                    'note'   => $this->__(
                        'Store to use for catalog product translation during products export and' .
                        ' for the currency (associated to the website) used during prices & stocks export.' .
                        ' Selected store view will also be used to create Magento orders when importing Mirakl orders.'
                    ),
                )
            );
        } else {
            $fieldset->addField(
                'store_id', 'hidden', array(
                    'name'  => 'store_id',
                    'value' => Mage::app()->getStore(true)->getId()
                )
            );
        }

        $fieldset->addField(
            'shop_id', 'text', array(
                'name'  => 'shop_id',
                'label' => $this->__('Shop Id'),
                'class' => 'validate-digits',
                'note'  => $this->__('If you are using multi-stores, you can target a specific shop id. ' .
                                     'Leave blank to use default shop of this connection.'),
            )
        );

        $form->addValues($data);

        $form->setAction($this->getUrl('*/*/save'));

        Mage::dispatchEvent(
            'mirakl_seller_prepare_connection_form_main', array(
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
        return $this->__('Connection Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('Information about the connection');
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
