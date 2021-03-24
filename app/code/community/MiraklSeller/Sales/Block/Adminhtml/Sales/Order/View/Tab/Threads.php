<?php

use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Sales_Block_Adminhtml_Sales_Order_View_Tab_Threads
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    /**
     * @var bool
     */
    protected $_pagerVisibility = false;

    /**
     * @var MiraklSeller_Api_Helper_Message
     */
    protected $_messageApi;

    /**
     * @var MiraklSeller_Core_Helper_Thread
     */
    protected $_threadHelper;

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_messageApi = Mage::helper('mirakl_seller_api/message');
        $this->_threadHelper = Mage::helper('mirakl_seller/thread');
    }

    /**
     * @return  $this
     */
    protected function _beforeToHtml()
    {
        if (!$this->getRequest()->isAjax()) {
            return $this;
        }

        return parent::_beforeToHtml();
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * {@inheritdoc}
     */
    public function getMainButtonsHtml()
    {
        return $this->getNewThreadButtonHtml();
    }

    /**
     * @return  string
     */
    public function getNewThreadButtonHtml()
    {
        return $this->getChildHtml('new_thread_button');
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        /** @var Mage_Adminhtml_Block_Widget_Button $buttonBlock */
        $buttonBlock = $this->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'order_id'      => $this->getRequest()->getParam('order_id'),
            'connection_id' => $this->getOrder()->getMiraklConnectionId(),
        );
        $buttonUrl = $this->getUrl('*/mirakl_seller_thread/view', $params);

        $buttonBlock->setData([
            'label'   => $this->__('Start a Conversation'),
            'class'   => 'order-thread-new',
            'onclick' => "MiraklThreads.processNew('" . $this->escapeUrl($buttonUrl) . "')",
        ]);

        $this->setChild('new_thread_button', $buttonBlock);

        return parent::_prepareLayout();
    }

    /**
     * @return  $this
     */
    protected function _prepareCollection()
    {
        $collection = new Varien_Data_Collection();

        if ($this->getRequest()->isAjax()) {
            try {
                $order = $this->getOrder();
                $connection = $this->getConnection();

                $threads = Mage::helper('mirakl_seller_api/message')
                    ->getThreads($connection, 'MMP_ORDER', $order->getMiraklOrderId());

                if ($threads->getCollection()->count()) {
                    /** @var \Mirakl\MMP\Common\Domain\Message\Thread\Thread $thread */
                    foreach ($threads->getCollection() as $thread) {
                        $data = $thread->getData();
                        $data['topic'] = $this->_threadHelper->getThreadTopic($connection, $thread);
                        $data['participant_names'] = $this->_threadHelper->getThreadCurrentParticipantsNames($thread);
                        $collection->addItem(new Varien_Object($data));
                    }
                }
            } catch (\Exception $e) {
                Mage::logException($e);
                $this->getLayout()
                    ->getMessagesBlock()
                    ->addError($e->getMessage());
            }
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepares grid columns
     *
     * @return  $this
     * @throws  Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('participants', array(
            'header'   => $this->__('Participants'),
            'index'    => 'participant_names',
            'filter'   => false,
            'sortable' => false,
            'getter'   => function ($row) {
                return implode(', ', $row->getParticipantNames()) . ' (' . $row->getMetadata()->getTotalCount() . ')';
            },
        ));

        $this->addColumn('topic', array(
            'header'   => $this->__('Topic'),
            'index'    => 'topic',
            'filter'   => false,
            'sortable' => false,
        ));

        $this->addColumn('date_updated', array(
            'type'     => 'datetime',
            'header'   => $this->__('Updated At'),
            'index'    => 'date_updated',
            'filter'   => false,
            'sortable' => false,
            'getter'   => function ($row) {
                return $row->getDateUpdated()
                    ->setTimezone(new \DateTimeZone('GMT'))
                    ->format('d/m/Y H:i:s');
            },
        ));

        $this->addColumn('action',
            array(
                'header'   => $this->__('Action'),
                'align'    => 'center',
                'type'     => 'action',
                'filter'   => false,
                'sortable' => false,
                'getter'   => 'getId',
                'actions'  => array(
                    array(
                        'caption' => $this->__('View Conversation'),
                        'field'   => 'thread_id',
                        'class'   => 'order-thread-view',
                        'url'     => array(
                            'base' => sprintf(
                                '*/mirakl_seller_thread/view/order_id/%d/connection_id/%d',
                                $this->getOrder()->getId(),
                                $this->getConnection()->getId()
                            )
                        ),
                    ),
                ),
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * {@inheritdoc}
     */
    protected function _afterToHtml($html)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_afterToHtml($html);
        }

        $afterHtml = $this->getLayout()
            ->createBlock('adminhtml/template')
            ->setTemplate('mirakl_seller/sales/order/view/mirakl_thread/js.phtml')
            ->toHtml();

        return $html . $afterHtml;
    }

    /**
     * @return  string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * Get tab class
     *
     * @return  string
     */
    public function getTabClass()
    {
        return 'marketplace ajax only';
    }

    /**
     * @return  string
     */
    public function getRowClickCallback()
    {
        return "
            function (grid, event) {
                var tr = Event.findElement(event, 'tr');
                MiraklThreads.processView(tr.title);
            }
        ";
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/mirakl_seller_thread/view', array(
            'order_id'      => $this->getOrder()->getId(),
            'connection_id' => $this->getConnection()->getId(),
            'thread_id'     => $row->getId()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return $this->__('Mirakl Messages');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return $this->__('Mirakl Messages');
    }

    /**
     * Get tab URL
     *
     * @return  string
     */
    public function getTabUrl()
    {
        return $this->getUrl('*/mirakl_seller_thread/list', array('_current' => true));
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return $this->getOrder()->getMiraklOrderId();
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}