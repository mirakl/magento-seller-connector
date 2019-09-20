<?php

use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Block_Adminhtml_Connection_Edit_Tab_Order
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

        $fieldset = $form->addFieldset('order_settings', array('legend' => $this->__('Order Settings')));

        /** @var Connection $connection */
        $connection = $this->getConnection();
        $data = $connection->getData();

        $fieldset->addField(
            'last_orders_synchronization_date', 'text',
            array(
                'name'  => 'last_orders_synchronization_date',
                'label' => $this->__('Last Synchronization Date'),
                'note'  => $this->__('This is the last synchronization date of the Mirakl orders.'),
            )
        );

        $form->addValues($data);

        Mage::dispatchEvent(
            'mirakl_seller_prepare_connection_form_order', array(
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
        return $this->__('Order Settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('Order settings of this connection');
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
