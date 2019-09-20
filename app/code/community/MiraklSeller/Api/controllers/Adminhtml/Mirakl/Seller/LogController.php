<?php

class MiraklSeller_Api_Adminhtml_Mirakl_Seller_LogController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/config/mirakl_seller_api_developer');
    }

    /**
     * Clears the API log file
     *
     * @return  $this
     */
    public function clearAction()
    {
        Mage::getSingleton('mirakl_seller_api/log_logger')->clear();

        $this->_getSession()->addSuccess($this->__('Log file has been cleared.'));

        return $this->_redirect('*/system_config/edit', array('section' => 'mirakl_seller_api_developer'));
    }

    /**
     * Downloads the API log file
     *
     * @return  $this
     */
    public function downloadAction()
    {
        $logger = Mage::getSingleton('mirakl_seller_api/log_logger');
        $fileName = basename($logger->getLogFile());

        $this->_prepareDownloadResponse($fileName, $logger->getLogFileContents());
    }
}
