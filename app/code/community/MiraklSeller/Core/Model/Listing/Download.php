<?php

class MiraklSeller_Core_Model_Listing_Download
{
    /**
     * @var MiraklSeller_Core_Model_Listing_Download_Adapter_Interface
     */
    protected $_adapter;

    /**
     * @var MiraklSeller_Core_Model_Listing_Export_Products
     */
    protected $_exportModel;

    /**
     * @param   array   $args
     */
    public function __construct($args = array())
    {
        $adapter = isset($args['adapter'])
            ? $args['adapter']
            : Mage::getModel('mirakl_seller/listing_download_adapter_csv');
        $this->setAdapter($adapter);

        $exportModel = isset($args['export_model'])
            ? $args['export_model']
            : Mage::getModel('mirakl_seller/listing_export_products');
        $this->setExportModel($exportModel);
    }

    /**
     * @param   MiraklSeller_Core_Model_Listing $listing
     * @return  string
     */
    public function prepare(MiraklSeller_Core_Model_Listing $listing)
    {
        $products = $this->_exportModel->export($listing);
        if (empty($products)) {
            return '';
        }

        foreach ($products as $data) {
            $this->_adapter->write($data);
        }

        return $this->_adapter->getContents();
    }

    /**
     * @return  string
     */
    public function getFileExtension()
    {
        return $this->_adapter->getFileExtension();
    }

    /**
     * @return  MiraklSeller_Core_Model_Listing_Export_Interface
     */
    public function getExportModel()
    {
        return $this->_exportModel;
    }

    /**
     * @param   MiraklSeller_Core_Model_Listing_Export_Interface    $exportModel
     * @return  $this
     */
    public function setExportModel(MiraklSeller_Core_Model_Listing_Export_Interface $exportModel)
    {
        $this->_exportModel = $exportModel;

        return $this;
    }

    /**
     * @return  MiraklSeller_Core_Model_Listing_Download_Adapter_Interface
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }

    /**
     * @param   MiraklSeller_Core_Model_Listing_Download_Adapter_Interface  $adapter
     * @return  $this
     */
    public function setAdapter(MiraklSeller_Core_Model_Listing_Download_Adapter_Interface $adapter)
    {
        $this->_adapter = $adapter;

        return $this;
    }
}
