<?php

class MiraklSeller_Process_Block_Adminhtml_Process_View extends Mage_Adminhtml_Block_Widget_Container
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->_addButton(
            'back', array(
                'label'   => Mage::helper('adminhtml')->__('Back'),
                'onclick' => "window.location.href = '" . $this->getUrl('*/*') . "'",
                'class'   => 'back',
            )
        );

        $process = $this->getProcess();

        if ($process->canRun()) {
            $confirmText = $this->__('Are you sure?');
            $this->addButton(
                'run', array(
                    'label'   => $this->__('Run'),
                    'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getRunUrl()}')",
                )
            );
        } elseif ($process->canStop()) {
            $confirmText = $this->__('Are you sure?');
            $this->addButton(
                'stop', array(
                    'label'   => $this->__('Stop'),
                    'onclick' => "confirmSetLocation('{$confirmText}', '{$this->getStopUrl()}')",
                )
            );
        }

        if ($process) {
            $this->_addButton(
                'delete', array(
                    'label'   => $this->__('Delete'),
                    'class'   => 'delete',
                    'onclick' => sprintf(
                        "deleteConfirm('%s', '%s')",
                        $this->jsQuoteEscape($this->__('Are you sure?')),
                        $this->getDeleteUrl()
                    ),
                )
            );
        }
    }

    /**
     * @return  string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', array('id' => $this->getProcess()->getId()));
    }

    /**
     * @return  MiraklSeller_Process_Model_Process
     */
    public function getProcess()
    {
        return Mage::registry('mirakl_seller_process');
    }

    /**
     * @return  string
     */
    public function getRunUrl()
    {
        return $this->getUrl('*/*/run', array('id' => $this->getProcess()->getId()));
    }

    /**
     * @return  string
     */
    public function getStopUrl()
    {
        return $this->getUrl('*/*/stop', array('id' => $this->getProcess()->getId()));
    }
}
