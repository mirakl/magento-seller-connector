<?php

class MiraklSeller_Api_Block_Adminhtml_Connection_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'mirakl_seller_api';

    /**
     * @var string
     */
    protected $_controller = 'adminhtml_connection';

    /**
     * @var string
     */
    protected $_objectId = 'id';

    /**
     * Initialization
     */
    public function __construct()
    {
        parent::__construct();

        $model = $this->getConnection();

        $this->_updateButton('save', 'label', $this->__('Save Connection'));

        if ($model && $model->getId()) {
            $this->_updateButton('delete', 'label', $this->__('Delete Connection'));
            $this->_updateButton(
                'delete', 'onclick', sprintf(
                    "deleteConfirm('%s', '%s')",
                    $this->jsQuoteEscape($this->__('Are you sure you want to delete this Mirakl connection?')),
                    $this->getDeleteUrl()
                )
            );
        }
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * @return  string
     */
    public function getHeaderText()
    {
        $model = $this->getConnection();
        if ($model && $model->getId()) {
            return $this->__("Edit Connection '%s'", $this->escapeHtml($model->getName()));
        }

        return $this->__('New Connection');
    }

    /**
     * @return  string
     */
    public function getHeaderCssClass()
    {
        return '';
    }

    /**
     * @return  string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save');
    }

    /**
     * @return  string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * @return  string
     * @throws  Exception
     */
    public function getDeleteUrl()
    {
        return $this->getUrl(
            '*/*/delete', array($this->_objectId => $this->getRequest()->getParam($this->_objectId))
        );
    }
}
