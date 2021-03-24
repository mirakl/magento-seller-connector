<?php

use GuzzleHttp\Exception\RequestException;
use Mirakl\MMP\Common\Domain\Shipping\Carrier;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Api_Model_Connection_Form_Builder_CarriersMapping
{
    /**
     * @var MiraklSeller_Api_Helper_Shipping
     */
    protected $_shippingHelper;

    /**
     * Initialization
     */
    public function __construct()
    {
        $this->_shippingHelper = Mage::helper('mirakl_seller_api/shipping');
    }

    /**
     * @param   Varien_Data_Form_Element_Fieldset $fieldset
     * @param   array                             $data
     * @param   array                             $params
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function prepareForm(Varien_Data_Form_Element_Fieldset $fieldset, &$data, $params = array())
    {
        if (!isset($params['values']) || !isset($params['connection'])) {
            return;
        }

        $carriersMapping = $this->_getCarriersMapping($params['values']);

        if (empty($carriersMapping)) {
            return;
        }

        try {
            $mappingValues = $this->_getMiraklCarriers($params['connection']);
        } catch (Exception $e) {
            $fieldset->setData('comment', $this->_shippingHelper->__($e->getMessage()));

            if (empty($params['values'])) {
                return;
            }

            $mappingValues = $this->_getMappedCarriers($params['values']);
        }

        foreach ($carriersMapping as $carrier) {
            $config = array(
                'name'     => sprintf('carriers_mapping[%s]', $carrier['magento_code']),
                'label'    => $carrier['magento_label'],
                'title'    => $carrier['magento_label'],
                'required' => false,
                'values'   => $mappingValues,
            );

            $element = $fieldset->addField('carriers_mapping_' . $carrier['magento_code'], 'select', $config);
            $element->setRenderer($this->_getElementRenderer());
        }
    }

    /**
     * @param   string|array    $originMapping
     * @param   int             $storeId
     * @return  array
     */
    private function _getCarriersMapping($originMapping, $storeId = null)
    {
        if (empty($originMapping)) {
            $originMapping = array();
        } else if (is_string($originMapping)) {
            $originMapping = json_decode($originMapping, true);
        }

        $key = array_map(function($item) {
            return isset($item['magento_code']) ? $item['magento_code'] : '';
        }, $originMapping);
        $originMapping = array_combine($key, $originMapping);

        $mapping = array();

        $carrierInstances = Mage::getSingleton('shipping/config')->getAllCarriers($storeId);
        foreach ($carrierInstances as $code => $carrier) {
            if ($carrier->isTrackingAvailable()) {
                $mapping[] = array(
                    'magento_code'   => $code,
                    'magento_label'  => $carrier->getConfigData('title'),
                    'mirakl_carrier' => isset($originMapping[$code]['mirakl_carrier']) ? $originMapping[$code]['mirakl_carrier'] : '',
                );
            }
        }

        return $mapping;
    }

    /**
     * @return  MiraklSeller_Api_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Element
     */
    protected function _getElementRenderer()
    {
        /** @var MiraklSeller_Api_Block_Adminhtml_Widget_Form_Renderer_Fieldset_Element $renderer */
        $renderer = Mage::getBlockSingleton('mirakl_seller/adminhtml_widget_form_renderer_fieldset_element');

        return $renderer;
    }

    /**
     * @param   Connection $connection
     * @return  array
     * @throws  Exception
     */
    protected function  _getMiraklCarriers(Connection $connection)
    {
        $options = array(
            array('value' => '', 'label' => $this->_shippingHelper->__('-- Please Select --')),
        );

        if (!$connection->getApiKey() || !$connection->getApiUrl()){
            throw new Exception('You need to save the connection before configuring the mapping');
        }

        try {
            $miraklCarriers = $this->_shippingHelper->getCarriers($connection);
        } catch (RequestException $e) {
            throw new Exception('Mirakl cannot be reached');
        }

        foreach ($miraklCarriers as $carrier) {
            /** @var Carrier $carrier */
            $options[] = array(
                'value' => $carrier->getCode(),
                'label' => $carrier->getLabel(),
            );
        }

        return $options;
    }

    /**
     * @param   array   $originMapping
     * @return  array
     */
    protected function  _getMappedCarriers($originMapping)
    {
        $options = array(
            array('value' => '', 'label' => $this->_shippingHelper->__('-- Please Select --')),
        );

        foreach ($originMapping as $miraklCarrier) {
            if ($miraklCarrier) {
                $options[] = array(
                    'value' => $miraklCarrier,
                    'label' => $miraklCarrier
                );
            }
        }

        return $options;
    }
}