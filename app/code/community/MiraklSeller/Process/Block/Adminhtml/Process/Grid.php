<?php

class MiraklSeller_Process_Block_Adminhtml_Process_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Initialize grid
     */
    public function __construct()
    {
        parent::__construct();
        $this->setId('processesGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setVarNameFilter('process_filter');
    }

    /**
     * Define collection model for current grid
     *
     * @return  Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('mirakl_seller_process/process')->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return  $this
     * @throws  Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'id', array(
                'header' => $this->__('Id'),
                'index'  => 'id',
                'width'  => '80px',
            )
        );

        $this->addColumn(
            'parent_id', array(
                'header'         => $this->__('Parent Id'),
                'index'          => 'parent_id',
                'frame_callback' => array($this, 'decorateParent'),
                'width'          => '80px',
            )
        );

        $this->addColumn(
            'type', array(
                'header' => $this->__('Type'),
                'index'  => 'type',
                'width'  => '120px',
            )
        );

        $this->addColumn(
            'name', array(
                'header' => $this->__('Name'),
                'index'  => 'name',
            )
        );

        $this->addColumn(
            'created_at', array(
                'header'         => $this->__('Created At'),
                'align'          => 'right',
                'index'          => 'created_at',
                'width'          => 1,
                'type'           => '',
                'frame_callback' => array($this, 'decorateCreatedAt'),
            )
        );

        $this->addColumn(
            'duration', array(
                'header'         => $this->__('Duration'),
                'align'          => 'right',
                'index'          => 'duration',
                'width'          => 1,
                'type'           => 'number',
                'frame_callback' => array($this, 'decorateDuration'),
            )
        );

        $this->addColumn(
            'file', array(
                'header'         => $this->__('File'),
                'index'          => 'file',
                'frame_callback' => array($this, 'decorateFile'),
                'filter'         => false,
            )
        );

        $this->addColumn(
            'output', array(
                'header'           => $this->__('Output'),
                'index'            => 'output',
                'frame_callback'   => array($this, 'decorateOutput'),
                'column_css_class' => 'pre',
            )
        );

        $statuses = array(
            'pending'    => $this->__('pending'),
            'processing' => $this->__('processing'),
            'completed'  => $this->__('completed'),
            'cancelled'  => $this->__('cancelled'),
            'stopped'    => $this->__('stopped'),
            'timeout'    => $this->__('timeout'),
            'error'      => $this->__('error'),
        );
        array_walk($statuses, function (&$value) {
            $value = ucfirst($value);
        });

        $this->addColumn(
            'status', array(
                'header'         => $this->__('Status'),
                'index'          => 'status',
                'type'           => 'options',
                'width'          => '80px',
                'options'        => $statuses,
                'frame_callback' => array($this, 'decorateStatus'),
            )
        );

        $this->addColumn(
            'mirakl_file', array(
                'header'         => $this->__('Error Report File'),
                'index'          => 'mirakl_file',
                'frame_callback' => array($this, 'decorateFile'),
                'filter'         => false,
            )
        );

        $this->addColumn(
            'action',
            array(
                'header'   => Mage::helper('adminhtml')->__('Action'),
                'width'    => '50px',
                'align'    => 'center',
                'type'     => 'action',
                'getter'   => 'getId',
                'filter'   => false,
                'sortable' => false,
                'actions'  => array(
                    array(
                        'caption' => $this->__('View'),
                        'url'     => array('base' => '*/*/view'),
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => $this->__('Delete'),
                        'url'     => array('base' => '*/*/delete'),
                        'field'   => 'id',
                        'confirm' => Mage::helper('adminhtml')->__('Are you sure?')
                    ),
                ),
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * @param   string  $createdAt
     * @return  string
     */
    public function decorateCreatedAt($createdAt)
    {
        return sprintf(
            '<span class="nobr">%s<br/>(%s)</span>',
            $this->formatDate($createdAt, 'short', true),
            $this->__('%s ago', Mage::helper('mirakl_seller_process')->getMoment($createdAt))
        );
    }

    /**
     * @param   int                                 $duration
     * @param   MiraklSeller_Process_Model_Process  $process
     * @return  string
     */
    public function decorateDuration($duration, MiraklSeller_Process_Model_Process $process)
    {
        return Mage::helper('mirakl_seller_process')->formatDuration($process->getDuration());
    }

    /**
     * @param   string                                  $filePath
     * @param   MiraklSeller_Process_Model_Process      $process
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return  string
     */
    public function decorateFile($filePath, $process, $column)
    {
        $isMirakl = strstr($column->getId(), 'mirakl') === false ? false : true;
        $html = '';
        if ($fileSize = $process->getFileSizeFormatted('&nbsp;', $isMirakl)) {
            $html = sprintf(
                '<a href="%s">%s</a>&nbsp;(%s)',
                $process->getFileUrl($isMirakl), $this->__('Download'), $fileSize
            );
            if ($process->canShowFile($isMirakl)) {
                $html .= sprintf(
                    '<br/> %s <a target="_blank" href="%s" title="%s">%s</a>',
                    $this->__('or'),
                    $this->getUrl('*/*/showFile', array('id' => $process->getId())),
                    $this->escapeHtml($this->__('Open in Browser')),
                    $this->escapeHtml($this->__('open in browser'))
                );
            }
        }

        return $html;
    }

    /**
     * @param   int $parentId
     * @return  string
     */
    public function decorateParent($parentId)
    {
        if (!$parentId) {
            return '-';
        }

        $url = sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getUrl('*/mirakl_seller_process/view', array('id' => $parentId)),
            $this->__('View Parent Process'),
            $parentId
        );

        return $url;
    }

    /**
     * @param   string                              $value
     * @param   MiraklSeller_Process_Model_Process  $process
     * @return  string
     */
    public function decorateOutput($value, MiraklSeller_Process_Model_Process $process)
    {
        $value = $process->getOutput();
        if (strlen($value)) {
            $lines = array_slice(explode("\n", $value), 0, 6);
            if (count($lines) === 6) {
                $lines[5] = '...';
            }

            array_walk(
                $lines, function (&$line) {
                    $line = Mage::helper('core/string')->truncate($line, 80);
                }
            );
            $value = implode('<br/>', $lines);
        }

        return $value;
    }

    /**
     * @param   string                                  $value
     * @param   MiraklSeller_Process_Model_Process      $process
     * @param   Mage_Adminhtml_Block_Widget_Grid_Column $column
     * @return  string
     */
    public function decorateStatus($value, $process, $column)
    {
        if (!$value) return '';

        $isMirakl = strstr($column->getId(), 'mirakl') === false ? false : true;

        return '<span class="' . $process->getStatusClass($isMirakl) . '"><span>' . $this->__($value) . '</span></span>';
    }

    /**
     * @param   Varien_Object   $row
     * @return  string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }

    /**
     * Add mass-actions to grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('processes');

        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label'    => $this->__('Delete'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'selected' => true,
                'confirm'  => Mage::helper('adminhtml')->__('Are you sure?'),
            )
        );

        return $this;
    }
}
