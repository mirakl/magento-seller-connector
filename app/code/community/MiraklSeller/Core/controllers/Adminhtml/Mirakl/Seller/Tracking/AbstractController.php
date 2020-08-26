<?php

use MiraklSeller_Core_Model_Listing_Tracking_Offer as OfferTracking;
use MiraklSeller_Core_Model_Listing_Tracking_Product as ProductTracking;

abstract class MiraklSeller_Core_Adminhtml_Mirakl_Seller_Tracking_AbstractController
    extends Mage_Adminhtml_Controller_Action
{
    /**
     * @return  string
     */
    abstract protected function _getModelClass();

    /**
     * @return  string
     */
    abstract protected function _getTrackingType();

    /**
     * @return  string
     */
    abstract protected function _getActiveTab();

    /**
     * @param   bool    $mustExists
     * @return  OfferTracking|ProductTracking
     */
    protected function _getTracking($mustExists = false)
    {
        /** @var OfferTracking|ProductTracking $tracking */
        $id = $this->getRequest()->getParam('id');
        $tracking = Mage::getModel($this->_getModelClass())->load($id);
        if ($mustExists && !$tracking->getId()) {
            $this->_getSession()->addError($this->__('This tracking no longer exists.'));
            $this->_redirect('*/*/');
            $this->getResponse()->sendHeadersAndExit();
        }

        return $tracking;
    }

    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed($this->_getModelClass());
    }

    /**
     * Download an error report
     */
    public function downloadReportAction()
    {
        $tracking = $this->_getTracking(true);
        $field = $this->getRequest()->getParam('field');

        try {
            if (!$tracking->hasData($field)) {
                Mage::throwException($this->__("Invalid field specified '%s'", $field));
            }

            $contents = $tracking->getData($field);
            $fileName = sprintf('tracking_%d_%s.csv', $tracking->getId(), $field);
            $this->getResponse()->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', 'application/octet-stream', true)
                ->setHeader('Content-Length', strlen($contents))
                ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName);

            $this->getResponse()->clearBody();
            $this->getResponse()->sendHeaders();

            session_write_close();

            $this->getResponse()->setBody($contents);
            $this->getResponse()->outputBody();
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());

            return $this->_redirect(
                '*/mirakl_seller_listing/edit', array(
                    'id'         => $tracking->getListingId(),
                    'active_tab' => $this->_getActiveTab(),
                )
            );
        }
    }

    /**
     * Delete multiple trackings at once
     */
    public function massDeleteAction()
    {
        $trackingIds = $this->getRequest()->getParam($this->_getActiveTab());

        try {
            Mage::getModel($this->_getModelClass())
                ->getResource()
                ->deleteIds($trackingIds);

            $this->_getSession()->addSuccess($this->__('Selected trackings have been deleted successfully.'));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        if ($listingId = $this->getRequest()->getParam('listing_id')) {
            return $this->_redirect(
                '*/mirakl_seller_listing/edit', array(
                    'id'         => $listingId,
                    'active_tab' => $this->_getActiveTab()
                )
            );
        }

        return $this->_redirectReferer();
    }

    /**
     * Update multiple trackings at once
     */
    public function massUpdateAction()
    {
        $trackingIds = $this->getRequest()->getParam($this->_getActiveTab());

        try {
            Mage::helper('mirakl_seller/tracking')->updateTrackingsByType($trackingIds, $this->_getTrackingType());

            $this->_getSession()
                ->addSuccess($this->__('Selected trackings will be updated asynchronously.'))
                ->addSuccess(
                    Mage::helper('mirakl_seller_process')->__(
                        'Click <a href="%s">here</a> to view process output.', $this->getUrl('*/mirakl_seller_process/list')
                    )
                );
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        if ($listingId = $this->getRequest()->getParam('listing_id')) {
            return $this->_redirect(
                '*/mirakl_seller_listing/edit', array(
                    'id'         => $listingId,
                    'active_tab' => $this->_getActiveTab()
                )
            );
        }

        return $this->_redirectReferer();
    }

    /**
     * Update the tracking
     */
    public function updateAction()
    {
        $tracking = $this->_getTracking(true);

        try {
            $processes = Mage::helper('mirakl_seller/tracking')->updateTrackingsByType(
                array($tracking->getId()), $this->_getTrackingType()
            );

            if (!count($processes)) {
                $this->_getSession()->addError($this->__('This tracking cannot be updated.'));
            } else {
                // Will contain only 1 process so run it synchronously
                foreach ($processes as $process) {
                    /** @var MiraklSeller_Process_Model_Process $process */
                    $process->run();

                    if ($process->isError()) {
                        $this->_getSession()->addError($this->__('An error occurred while updating the tracking.'));
                    } else {
                        $this->_getSession()->addSuccess($this->__('The tracking has been updated successfully.'));
                    }
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect(
            '*/mirakl_seller_listing/edit', array(
                'id'         => $tracking->getListingId(),
                'active_tab' => $this->_getActiveTab(),
            )
        );
    }
}
