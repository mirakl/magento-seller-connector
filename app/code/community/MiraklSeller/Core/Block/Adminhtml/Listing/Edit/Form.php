<?php

class MiraklSeller_Core_Block_Adminhtml_Listing_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
