<?php

class MiraklSeller_Api_Block_Adminhtml_Connection_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Initialization
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('connection_form');
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
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
        $form = new Varien_Data_Form(
            array(
                'id'     => 'edit_form',
                'action' => $this->getData('action'),
                'method' => 'post',
            )
        );

        $connection = $this->getConnection();

        if ($connection->getId()) {
            $form->addField('id', 'hidden', array('name' => 'id'));
            $form->setValues($connection->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
