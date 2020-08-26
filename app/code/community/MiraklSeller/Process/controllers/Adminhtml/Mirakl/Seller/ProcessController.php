<?php

class MiraklSeller_Process_Adminhtml_Mirakl_Seller_ProcessController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return  MiraklSeller_Process_Model_Process
     */
    protected function _getProcess()
    {
        $id = (int) $this->getRequest()->getParam('id');

        /** @var MiraklSeller_Process_Model_Process $process */
        $process = Mage::getModel('mirakl_seller_process/process')->load($id);

        return $process;
    }

    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mirakl_seller/processes');
    }

    /**
     * @param   string  $message
     * @return  $this
     */
    protected function _redirectErrorMessage($message)
    {
        $this->_getSession()->addError($message);

        return $this->_redirect('*/*/list');
    }

    /**
     * Runs first pending process asynchronously if config enabled and handle processes in timeout
     *
     * @throws  Zend_Controller_Response_Exception
     */
    public function asyncAction()
    {
        ob_start();

        $body = array();
        $config = Mage::helper('mirakl_seller_process/config');

        $process = null;
        if ($config->isAutoAsyncExecution()) {
            $process = Mage::helper('mirakl_seller_process')->getPendingProcess();
            $body[] = $process ? $this->__('Processing #%s', $process->getId()) : $this->__('Nothing to process asynchronously');
        } else {
            $body[] = $this->__('Automatic process execution is disabled');
        }

        $stopped = Mage::helper('mirakl_seller_process/error')->stopProcessesInError();
        $body[] = $this->__('%d process%s in error stopped', $stopped, $stopped > 1 ? 'es' : '');

        if ($delay = $config->getTimeoutDelay()) {
            try {
                $updated = Mage::getResourceModel('mirakl_seller_process/process')->markAsTimeout($delay);
                $body[] = $this->__('%d process%s in timeout', $updated, $updated > 1 ? 'es' : '');
            } catch (\Exception $e) {
                $body[] = $e->getMessage();
            }
        }

        $this->getResponse()
            ->setBody(implode(' / ', $body))
            ->sendResponse();

        session_write_close();
        ob_end_flush();
        flush();

        if ($process) {
            $process->run();
        }

        $this->getResponse()->clearBody();

        $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
    }

    /**
     * Delete all processes
     */
    public function clearAction()
    {
        try {
            Mage::getResourceModel('mirakl_seller_process/process')->truncate();

            $this->_getSession()->addSuccess($this->__('Processes have been deleted successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError(
                $this->__('An error occurred while deleting all processes: %s.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/list');
    }

    /**
     * Delete a process
     */
    public function deleteAction()
    {
        try {
            $process = $this->_getProcess();
            if (!$process->getId()) {
                return $this->_redirectErrorMessage($this->__('This process no longer exists.'));
            }

            $process->delete();

            $this->_getSession()->addSuccess($this->__('Process has been deleted successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError(
                $this->__('An error occurred while deleting the process: %s.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/list');
    }

    /**
     * Forward to list
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * Mass delete action
     */
    public function massDeleteAction()
    {
        $processIds = $this->getRequest()->getParam('processes');

        try {
            Mage::getModel('mirakl_seller_process/process')
                ->getResource()
                ->deleteIds($processIds);

            $this->_getSession()->addSuccess($this->__('Processes have been deleted successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError(
                $this->__('An error occurred while deleting processes: %s.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/list');
    }

    /**
     * List processes
     */
    public function listAction()
    {
        $this->_title($this->__('Mirakl'))
            ->_title($this->__('Process Report List'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/processes');
        $this->renderLayout();
    }

    /**
     * Execute a process
     */
    public function runAction()
    {
        $process = $this->_getProcess();

        if (!$process->getId()) {
            return $this->_redirectErrorMessage($this->__('This process no longer exists.'));
        }

        if (!$process->canRun()) {
            return $this->_redirectErrorMessage($this->__('This process cannot be executed.'));
        }

        try {
            $process->run(true);

            $this->_getSession()->addSuccess($this->__('Process has been executed successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError(
                $this->__('An error occurred while executing the process: %s.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/view', array('id' => $process->getId()));
    }

    /**
     * Show process file contents
     */
    public function showFileAction()
    {
        $process = $this->_getProcess();
        if (!$process->getId()) {
            return $this->_redirectErrorMessage($this->__('This process no longer exists.'));
        }

        $file = $this->getRequest()->getParam('mirakl', false) ? $process->getMiraklFile() : $process->getFile();

        $fh = fopen($file, 'r');
        $fgetcsv = function () use ($fh) {
            return fgetcsv($fh, 0, ';', '"');
        };

        if (count($fgetcsv()) > 1) {
            // Parse CSV and show as HTML table
            fseek($fh, 0);
            $body = '<table border="1" cellpadding="2" style="border-collapse: collapse; border: 1px solid #aaa;">';
            while ($data = $fgetcsv()) {
                $body .= sprintf(
                    '<tr>%s</tr>', implode(
                        '', array_map(
                            function ($value) {
                                if (preg_match('#^(https?:\/\/.+)$#', $value)) {
                                    $value = sprintf('<a href="%1$s" target="_blank">%1$s</a>', $value);
                                } else {
                                    $value = htmlspecialchars($value);
                                }

                                return '<td>' . $value . '</td>';
                            }, $data
                        )
                    )
                );
            }

            $body .= '</table>';
        } else {
            // Show raw contents
            $body = '<pre>' . htmlentities(file_get_contents($file)) . '</pre>';
        }

        $this->getResponse()
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($body)
            ->sendResponse();

        $this->getResponse()->clearBody();

        $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
    }

    /**
     * Stop a process
     */
    public function stopAction()
    {
        $process = $this->_getProcess();
        if (!$process->getId()) {
            return $this->_redirectErrorMessage($this->__('This process no longer exists.'));
        }

        try {
            $process->stop(MiraklSeller_Process_Model_Process::STATUS_STOPPED);

            $process->getChildrenCollection()
                ->walk('cancel', array('Cancelled because parent has been stopped'));

            $this->_getSession()->addSuccess($this->__('Process has been stopped successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError(
                $this->__('An error occurred while stopping the process: %s.', $e->getMessage())
            );
        }

        $this->_redirect('*/*/view', array('id' => $process->getId()));
    }

    /**
     * View report
     */
    public function viewAction()
    {
        $process = $this->_getProcess();
        if (!$process->getId()) {
            return $this->_redirectErrorMessage($this->__('This process no longer exists.'));
        }

        Mage::register('mirakl_seller_process', $process);

        $this->_title($this->__('View Process Report'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/processes');

        return $this->renderLayout();
    }
}
