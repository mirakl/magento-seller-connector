<?php

use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;
use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use Mirakl\MMP\Shop\Domain\Collection\Reason\ReasonCollection;
use MiraklSeller_Api_Model_Connection as Connection;

/**
 * @method bool  getShowForm()
 * @method $this setShowForm(bool $showForm)
 */
class MiraklSeller_Sales_Block_Adminhtml_Sales_Order_View_Thread_View extends Mage_Adminhtml_Block_Template
{
    /**
     * @var string
     */
    protected $_template = 'mirakl_seller/sales/order/view/mirakl_thread/view.phtml';

    /**
     * @param   ThreadAttachment    $attachment
     * @return  string
     */
    public function getAttachmentUrl(ThreadAttachment $attachment)
    {
        $thread = $this->getThread();

        return $this->getUrl('*/mirakl_seller_thread/attachment', [
            'order_id'      => $this->getRequest()->getParam('order_id'),
            'connection_id' => $this->getConnection()->getId(),
            'thread_id'     => $thread->getId(),
            'attachment_id' => $attachment->getId(),
        ]);
    }

    /**
     * @return  Connection
     */
    public function getConnection()
    {
        return Mage::registry('mirakl_seller_connection');
    }

    /**
     * @return  string
     */
    public function getFormAction()
    {
        if ($this->getThread()) {
            return $this->getUrl('*/mirakl_seller_thread/reply');
        }

        return $this->getUrl('*/mirakl_seller_thread/new');
    }

    /**
     * @return  string
     */
    public function getFormTitle()
    {
        return $this->getThread() ? $this->__('Answer') : $this->__('Start a Conversation');
    }

    /**
     * @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    /**
     * @return  ThreadDetails
     */
    public function getThread()
    {
        return Mage::registry('mirakl_seller_thread');
    }

    /**
     * @param   ThreadMessage   $message
     * @return  array
     */
    public function getRecipientNames(ThreadMessage $message)
    {
        $names = array();

        $message = $message->toArray();

        if (!empty($message['to'])) {
            foreach ($message['to'] as $recipient) {
                if (!empty($recipient['display_name'])) {
                    $names[] = $recipient['display_name'];
                }
            }
        }

        return $names;
    }

    /**
     * @param   ThreadMessage   $message
     * @return  string
     */
    public function getSenderName(ThreadMessage $message)
    {
        $message = $message->toArray();

        if (isset($message['from']['organization_details']['display_name'])) {
            return $message['from']['organization_details']['display_name'];
        }

        return $message['from']['display_name'];
    }

    /**
     * @return  ReasonCollection
     */
    public function getThreadReasons()
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();

        return Mage::helper('mirakl_seller_api/reason')
            ->getTypeReasons($this->getConnection(), ReasonType::ORDER_MESSAGING, $locale);
    }

    /**
     * @return  array
     */
    public function getThreadRecipients()
    {
        return array(
            'CUSTOMER' => $this->__('Customer'),
            'OPERATOR' => $this->__('Operator'),
            'BOTH'     => $this->__('Customer and Operator'),
        );
    }

    /**
     * @param   ThreadMessage   $message
     * @return  bool
     */
    public function isSellerMessage(ThreadMessage $message)
    {
        return $message->getFrom()->getType() == 'SHOP_USER';
    }
}