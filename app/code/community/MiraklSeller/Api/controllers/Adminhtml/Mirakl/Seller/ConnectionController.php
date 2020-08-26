<?php

class MiraklSeller_Api_Adminhtml_Mirakl_Seller_ConnectionController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @param   bool    $mustExists
     * @return  MiraklSeller_Api_Model_Connection
     */
    protected function _getConnection($mustExists = false)
    {
        $id = $this->getRequest()->getParam('id');
        $connection = Mage::getModel('mirakl_seller_api/connection')->load($id);
        if ($mustExists && !$connection->getId()) {
            $this->_getSession()->addError($this->__('This connection no longer exists.'));
            $this->_redirect('*/*/');
            $this->getResponse()->sendHeadersAndExit();
        }

        return $connection;
    }

    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mirakl_seller/connections');
    }

    /**
     * Delete a Connection
     *
     * @return $this
     */
    public function deleteAction()
    {
        try {
            $connection = $this->_getConnection(true);
            $connection->delete();
            $this->_getSession()->addSuccess($this->__('The connection has been deleted.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Create or edit connection
     */
    public function editAction()
    {
        $mustExists = $this->getRequest()->has('id');
        $connection = $this->_getConnection($mustExists);

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $connection->setData($data);
        }

        Mage::register('mirakl_seller_connection', $connection);

        $this->_title($this->__('Edit Connection'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/connections');

        return $this->renderLayout();
    }

    /**
     * Forward to connections list
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * List connections
     */
    public function listAction()
    {
        $this->_title($this->__('Mirakl'))
            ->_title($this->__('Connection List'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/connections');
        $this->renderLayout();
    }

    /**
     * New connection form
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Save a connection
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $connection = $this->_getConnection();

            try {
                $this->_getSession()->setFormData($data);

                $connection->setData($data);
                $connection->save();
                $this->_getSession()->setFormData(false);
                $connection->validate();

                $this->_getSession()->addSuccess($this->__('The connection has been saved.'));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } finally {
                if ($connection->getId()) {
                    return $this->_redirect('*/*/edit', array('id' => $connection->getId()));
                }
            }
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Test a connection
     */
    public function testAction()
    {
        $message = $this->__('Test connection:');
        try {
            $connection = $this->_getConnection(true);
            $connection->validate();
            $message .= ' ' . $this->__('SUCCESS');
            $this->_getSession()->addSuccess($message);
        } catch (Exception $e) {
            $message .= '<br />' . $e->getMessage();
            $this->_getSession()->addError($message);
        }

        return $this->_redirect('*/*/');
    }
}
