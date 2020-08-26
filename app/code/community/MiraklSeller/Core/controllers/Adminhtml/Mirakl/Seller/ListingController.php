<?php

use MiraklSeller_Core_Model_Listing as Listing;

class MiraklSeller_Core_Adminhtml_Mirakl_Seller_ListingController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @param   bool    $mustExists
     * @param   int     $listingId
     * @return  Listing
     */
    protected function _getListing($mustExists = false, $listingId = null)
    {
        if (empty($listingId)) {
            $listingId = $this->getRequest()->getParam('id');
        }

        $listing = Mage::getModel('mirakl_seller/listing')->load($listingId);

        if ($mustExists && !$listing->getId()) {
            $this->_getSession()->addError($this->__('This listing no longer exists.'));
            $this->_redirect('*/*/');
            $this->getResponse()->sendHeadersAndExit();
        }

        return $listing;
    }

    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mirakl_seller/listings');
    }

    /**
     * Remove all listing products without exporting the deletion to Mirakl
     */
    public function clearAllAction()
    {
        try {
            $listing = $this->_getListing(true);
            Mage::getResourceModel('mirakl_seller/offer')->deleteListingOffers($listing->getId());
            $this->_getSession()->addSuccess($this->__('Products cleared successfully.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirectReferer();
    }

    /**
     * Redirect to the new page with the choosen connection
     */
    public function connectionAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            try {
                $this->_getSession()->setFormData($data);

                if (!isset($data['connection_id']) || empty($data['connection_id'])) {
                    Mage::throwException('Please provide a connection to associate with the listing');
                }

                return $this->_redirect('*/*/new', array('connection' => $data['connection_id']));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());

                return $this->_redirect('*/*/new');
            }
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Delete a listing
     */
    public function deleteAction()
    {
        try {
            $listing = $this->_getListing(true);
            $listing->delete();
            $this->_getSession()->addSuccess($this->__('The listing has been deleted.'));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirect('*/*/');
    }

    /**
     * Download listing associated products
     */
    public function downloadAction()
    {
        $listing = $this->_getListing(true);

        /** @var MiraklSeller_Core_Model_Listing_Download $downloader */
        $downloader = Mage::getModel('mirakl_seller/listing_download');

        try {
            $contents = $downloader->prepare($listing);
            $fileName = sprintf('listing_products_%d.%s', $listing->getId(), $downloader->getFileExtension());
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

            return $this->_redirect('*/*/');
        }
    }

    /**
     * Create or edit a listing
     */
    public function editAction()
    {
        $mustExists = $this->getRequest()->has('id');
        $listing = $this->_getListing($mustExists);

        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $listing->setData($data);
        }

        Mage::register('mirakl_seller_listing', $listing);

        $this->_title($this->__('Edit Listing'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/listings');

        return $this->renderLayout();
    }

    /**
     * Export products of the listing
     *
     * @retrun  Mage_Adminhtml_Controller_Action
     */
    public function exportProductAction()
    {
        $mode = strtoupper($this->getRequest()->getParam('export_mode'));
        if (!in_array($mode, Listing::getAllowedProductModes())) {
            $this->_getSession()->addError($this->__('This mode is not supported'));

            return $this->_redirectReferer();
        }

        return $this->_exportAction(Listing::TYPE_PRODUCT, true, $mode);
    }

    /**
     * Export offers of the listing
     *
     * @retrun  Mage_Adminhtml_Controller_Action
     */
    public function exportOfferAction()
    {
        return $this->_exportAction(Listing::TYPE_OFFER);
    }

    /**
     * Export the listing
     *
     * @param   string  $type
     * @param   bool    $offerFull
     * @param   string  $productMode
     * @return  Mage_Adminhtml_Controller_Action
     */
    protected function _exportAction(
        $type,
        $offerFull = true,
        $productMode = Listing::PRODUCT_MODE_PENDING
    ) {
        $listing = $this->_getListing(true);

        try {
            $processes = Mage::helper('mirakl_seller/listing')
                ->export($listing, $type, $offerFull, $productMode);

            if (count($processes) === 1) {
                $url = $processes[0]->getUrl();
            } else {
                $url = $this->getUrl('*/mirakl_seller_process/list');
            }

            $this->_getSession()
                ->addSuccess($this->__('The process to export the listing will be executed in parallel.'))
                ->addSuccess(
                    Mage::helper('mirakl_seller_process')->__(
                        'Click <a href="%s">here</a> to view process output.', $url
                    )
                );
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirectReferer();
    }

    /**
     * Mark multiple offers from listing as new at once
     */
    public function massNewOfferAction()
    {
        $listing = $this->_getListing(true);
        $productIds = $this->getRequest()->getParam('products');

        try {
            $offerResource = Mage::getResourceModel('mirakl_seller/offer');
            $offerResource->markOffersAsNew($listing->getId(), $productIds);
            $offerResource->markProductsAsNew($listing->getId(), $productIds);

            $this->_getSession()->addSuccess($this->__('Selected prices & stocks will be exported with product info during the next export'));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }

        return $this->_redirectReferer();
    }

    /**
     * Product grid for AJAX request
     */
    public function productGridAction()
    {
        Mage::register('mirakl_seller_listing', $this->_getListing(true));

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Tracking offer grid for AJAX request
     */
    public function trackingOfferGridAction()
    {
        Mage::register('mirakl_seller_listing', $this->_getListing(true));

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Tracking product grid for AJAX request
     */
    public function trackingProductGridAction()
    {
        Mage::register('mirakl_seller_listing', $this->_getListing(true));

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Forward to listing list
     */
    public function indexAction()
    {
        $this->_forward('list');
    }

    /**
     * List listings
     */
    public function listAction()
    {
        $this->_title($this->__('Mirakl'))
            ->_title($this->__('Listing List'));
        $this->loadLayout();
        $this->_setActiveMenu('mirakl_seller/listings');
        $this->renderLayout();
    }

    /**
     * New listing form
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Refresh listing products
     *
     * @param   bool    $synchronous
     * @param   int     $listingId
     */
    protected function _refreshProducts($synchronous = false, $listingId = null)
    {
        try {
            $listing = $this->_getListing(true, $listingId);

            /** @var MiraklSeller_Process_Model_Process $process */
            $process = Mage::helper('mirakl_seller/listing')->refresh($listing);

            $session = $this->_getSession();

            if ($synchronous) {
                $process->run();
                $session->addSuccess($this->__('The list of Products / Prices & Stocks has been refreshed'));
            } else {
                $session->addSuccess($this->__('The process to refresh the listing will be executed in parallel.'));
            }

            $session->addSuccess($this->__('Click <a href="%s">here</a> to view process output.', $process->getUrl()));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
        }
    }

    /**
     * Refresh the listing's products
     */
    public function refreshAction()
    {
        $this->_refreshProducts();

        return $this->_redirectReferer();
    }

    /**
     * Save a listing
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $listing = $this->_getListing();
            try {
                $this->_getSession()->setFormData($data);

                if (!$listing->getId() && (!isset($data['connection_id']) || empty($data['connection_id']))) {
                    Mage::throwException('Please provide a connection to associate with the listing');
                }

                // Save builder parameters
                $builderParams = isset($data['rule']) ? $data['rule'] : array();
                $listing->setBuilderParams($builderParams);
                unset($data['rule']);

                // Save variants attributes
                $variantsAttributes = isset($data['variants_attributes']) ? $data['variants_attributes'] : array();
                $listing->setVariantsAttributes($variantsAttributes);
                unset($data['variants_attributes']);

                $listing->addData($data);

                if (isset($data['additional_fields'])) {
                    $listing->setOfferAdditionalFieldsValues($data['additional_fields']);
                }

                $listing->save();

                $this->_getSession()->addSuccess($this->__('The listing has been saved.'));
                $this->_getSession()->setFormData(false);

                // Refresh product action
                $this->_refreshProducts(true, $listing->getId());

                return $this->_redirect('*/*/edit', array('id' => $listing->getId()));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());

                return $this->_redirect('*/*/edit', array('id' => $listing->getId()));
            }
        }

        return $this->_redirect('*/*/');
    }
}
