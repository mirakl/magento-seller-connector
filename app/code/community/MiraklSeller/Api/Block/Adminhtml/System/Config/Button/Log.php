<?php

class MiraklSeller_Api_Block_Adminhtml_System_Config_Button_Log extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @var array
     */
    protected $buttonsConfig = array(
        array(
            'label'   => 'Download',
            'title'   => 'Download log file',
            'url'     => '*/mirakl_seller_log/download',
            'class'   => 'scalable',
        ),
        array(
            'label'   => 'Clear',
            'title'   => 'Clear log file',
            'url'     => '*/mirakl_seller_log/clear',
            'confirm' => 'Are you sure? This will erase all API log contents.',
            'class'   => 'scalable primary',
        ),
    );

    /**
     * {@inheritdoc}
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $html = '';

        $helper = Mage::helper('mirakl_seller_api');
        $logFileSize = Mage::getSingleton('mirakl_seller_api/log_logger')->getLogFileSize();

        foreach ($this->buttonsConfig as $buttonConfig) {
            /** @var Mage_Adminhtml_Block_Widget_Button $button */
            $button = $this->getLayout()->createBlock('adminhtml/widget_button');
            $button->setType('button')
                ->setLabel($helper->__($buttonConfig['label']))
                ->setClass($buttonConfig['class']);

            if (isset($buttonConfig['title'])) {
                $button->setTitle($helper->__($buttonConfig['title']));
            }

            if (isset($buttonConfig['url'])) {
                $url = $this->getUrl($buttonConfig['url']);
                $button->setOnclick("setLocation('$url');");

                if (isset($buttonConfig['confirm'])) {
                    $confirm = $helper->__($buttonConfig['confirm']);
                    $button->setOnclick("confirmSetLocation('$confirm', '$url');");
                }
            }

            if (isset($buttonConfig['onclick'])) {
                $button->setOnclick($buttonConfig['onclick']);
            }

            if (!$logFileSize) {
                $button->setDisabled(true);
            }

            $html .= $button->toHtml() . ' ';
        }

        if (!$logFileSize) {
            $html .= $helper->__('(log file is empty)');
        } else {
            $html .= '(' . $helper->formatSize($logFileSize) . ')';
        }

        return $html;
    }
}
