<?php

use GuzzleHttp\Exception\BadResponseException;
use Mirakl\Core\Domain\FileWrapper;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadAttachment;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadDetails;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadMessage;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadReplyMessageInput;
use Mirakl\MMP\Common\Domain\Order\Message\CreateOrderThread;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Sales_Adminhtml_Mirakl_Seller_ThreadController extends Mage_Adminhtml_Controller_Action
{
    /**
     * @var MiraklSeller_Api_Helper_Order
     */
    protected $_apiOrder;

    /**
     * @var MiraklSeller_Api_Helper_Message
     */
    protected $_apiMessage;

    /**
     * Initialization
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_apiOrder   = Mage::helper('mirakl_seller_api/order');
        $this->_apiMessage = Mage::helper('mirakl_seller_api/message');
    }

    /**
     * @param   int $connectionId
     * @return  Connection
     * @throws  Mage_Core_Exception
     */
    protected function _getConnection($connectionId)
    {
        /** @var Connection $connection */
        $connection = Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
        if (!$connection->getId()) {
            $error = $this->__('Could not find connection with id %s', $connectionId);
            Mage::throwException($error);
        }

        return $connection;
    }

    /**
     * @param   Connection  $connection
     * @param   int         $miraklOrderId
     * @return  \Mirakl\MMP\Shop\Domain\Order\ShopOrder
     * @throws  Mage_Core_Exception
     */
    protected function _getMiraklOrder(Connection $connection, $miraklOrderId)
    {
        if (!$miraklOrderId) {
            $error = $this->__('Mirakl order id could not be found');
            Mage::throwException($error);
        }

        $order = $this->_apiOrder->getOrderById($connection, $miraklOrderId);
        if (!$order) {
            $error = $this->__('Could not find order with id %s on connection %s', $miraklOrderId, $connection->getName());
            Mage::throwException($error);
        }

        return $order;
    }

    /**
     * @return  Mage_Sales_Model_Order
     * @throws  Mage_Core_Exception
     */
    protected function _getOrder()
    {
        $id = $this->getRequest()->getParam('order_id');

        /** @var Mage_Sales_Model_Order $order */
        $order = Mage::getModel('sales/order')->load($id);

        if (!$order->getId()) {
            Mage::throwException($this->__('This order no longer exists.'));
        }

        return $order;
    }

    /**
     * @return  bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('mirakl_seller/orders');
    }

    /**
     * Handle a thread attachment
     */
    public function attachmentAction()
    {
        try {
            // Retrieve Magento order
            $order = $this->_getOrder();

            // Retrieve Mirakl connection
            $connection = $this->_getConnection($order->getMiraklConnectionId());

            // Retrieve Mirakl thread
            $threadId = $this->getRequest()->getParam('thread_id');
            $miraklThread = $this->_getMiraklThread($connection, $threadId);

            $attachmentId = $this->getRequest()->getParam('attachment_id');

            if (!$this->_validateAttachment($miraklThread, $attachmentId)) {
                Mage::throwException($this->__('Attachment not found.'));
            }

            $document = $this->_apiMessage->downloadThreadMessageAttachment($connection, $attachmentId);
            $contentSize = $document->getFile()->fstat()['size'];

            $this->getResponse()->setHttpResponseCode(200)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Content-type', 'application/octet-stream', true)
                ->setHeader('Content-Length', $contentSize)
                ->setHeader('Content-Disposition', 'attachment; filename=' . $document->getFileName());

            $this->getResponse()->clearBody();
            $this->getResponse()->sendHeaders();

            session_write_close();

            $this->getResponse()->setBody($document->getFile()->fread($contentSize));
            $this->getResponse()->outputBody();
        } catch (\Exception $e) {
            $this->_getSession()->addError($e->getMessage());

            return $this->_redirect('*/sales_order/index');
        }
    }

    /**
     * Display Mirakl order threads
     */
    public function listAction()
    {
        try {
            // Retrieve Magento order
            $order = $this->_getOrder();

            // Retrieve Mirakl connection
            $connection = $this->_getConnection($order->getMiraklConnectionId());

            // Retrieve Mirakl order
            $miraklOrder = $this->_getMiraklOrder($connection, $order->getMiraklOrderId());

            Mage::register('current_order', $order);
            Mage::register('mirakl_seller_connection', $connection);
            Mage::register('mirakl_seller_order', $miraklOrder);

            $html = $this->getLayout()
                ->createBlock('mirakl_seller_sales/adminhtml_sales_order_view_tab_threads')
                ->toHtml();
        } catch (Exception $e) {
            $html = $this->getLayout()
                ->getMessagesBlock()
                ->addError($e->getMessage())
                ->toHtml();
        }

        $this->getResponse()->setBody($html);
    }

    /**
     * @param   Connection  $connection
     * @param   string      $threadId
     * @return  ThreadDetails
     */
    public function _getMiraklThread(Connection $connection, $threadId)
    {
        return $this->_apiMessage->getThreadDetails($connection, $threadId);
    }

    /**
     * @param   ThreadDetails   $thread
     * @param   string          $attachmentId
     * @return  bool
     */
    protected function _validateAttachment(ThreadDetails $thread, $attachmentId)
    {
        /** @var ThreadMessage $message */
        foreach ($thread->getMessages() as $message) {
            if (!empty($message->getAttachments())) {
                /** @var ThreadAttachment $attachment */
                foreach ($message->getAttachments() as $attachment) {
                    if ($attachment->getId() == $attachmentId) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Handle thread creation form
     */
    public function newAction()
    {
        try {
            $connectionId = $this->getRequest()->getPost('connection_id');
            $connection = $this->_getConnection($connectionId);

            $order = $this->_getOrder();
            $miraklOrder = $this->_apiOrder->getOrderById($connection, $order->getMiraklOrderId());

            $data = $this->getRequest()->getPost();

            if (empty($data['recipients']) || empty($data['topic']) || empty($data['body'])) {
                Mage::throwException($this->__('Missing or invalid data specified.'));
            }

            $messageInput = array(
                'topic' => [
                    'type' => 'REASON_CODE',
                    'value' => $data['topic']
                ],
                'body'  => nl2br($data['body']),
                'to'    => $this->_getCreateTo($data['recipients']),
            );

            // Send thread creation to Mirakl (API OR43)
            $this->_apiOrder->createOrderThread(
                $connection,
                $miraklOrder,
                new CreateOrderThread($messageInput),
                $this->_prepareFiles()
            );

            $this->_getSession()->addSuccess($this->__('Your message has been sent successfully.'));
        } catch (BadResponseException $e) {
            $response = \Mirakl\parse_json_response($e->getResponse());
            $message = $response['message'] ?? $e->getMessage();
            $this->_getSession()->addError($this->__('An error occurred: %s', $message));
        } catch (\Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred: %s', $e->getMessage()));
        }

        $this->_redirect('*/sales_order/view', array(
            'order_id' => $this->getRequest()->getParam('order_id'),
            '_query' => array('active_tab' => 'mirakl_seller_threads')
        ));
    }

    /**
     * Handle thread reply form
     */
    public function replyAction()
    {
        try {
            $connectionId = $this->getRequest()->getPost('connection_id');
            $connection = $this->_getConnection($connectionId);

            $threadId = $this->getRequest()->getPost('thread_id');
            $thread = $this->_getMiraklThread($connection, $threadId);

            $data = $this->getRequest()->getPost();

            if (empty($data['recipients']) || empty($data['body'])) {
                Mage::throwException($this->__('Missing or invalid data specified.'));
            }

            $messageInput = array(
                'body' => nl2br($data['body']),
                'to'   => $this->_getReplyTo($thread, $data['recipients']),
            );

            // Send the message to Mirakl (API M12)
            $this->_apiMessage->replyToThread(
                $connection,
                $thread->getId(),
                new ThreadReplyMessageInput($messageInput),
                $this->_prepareFiles()
            );

            $this->_getSession()->addSuccess($this->__('Your message has been sent successfully.'));
        } catch (BadResponseException $e) {
            $message = $e->getMessage();
            $response = \Mirakl\parse_json_response($e->getResponse());
            if (!empty($response['message'])) {
                $message = $response['message'];
            } elseif (!empty($response['errors'][0]['message'])) {
                $message = $response['errors'][0]['message'];
            }
            $this->_getSession()->addError($this->__('An error occurred: %s', $message));
        } catch (\Exception $e) {
            $this->_getSession()->addError($this->__('An error occurred: %s', $e->getMessage()));
        }

        $this->_redirect('*/sales_order/view', array(
            'order_id' => $this->getRequest()->getParam('order_id'),
            '_query' => array('active_tab' => 'mirakl_seller_threads')
        ));
    }

    /**
     * @param   string  $key
     * @return  FileWrapper[]
     */
    protected function _prepareFiles($key = 'file')
    {
        if (empty($_FILES[$key])) {
            return array();
        }

        $files = array();
        $fileData = $_FILES[$key];

        if ($fileData && !empty($fileData['tmp_name'])) {
            $file = new FileWrapper(new \SplFileObject($fileData['tmp_name']));
            $file->setContentType($fileData['type']);
            $file->setFileName($fileData['name']);
            $files[] = $file;
        }

        return $files;
    }

    /**
     * Display Mirakl thread view
     */
    public function viewAction()
    {
        try {
            // Retrieve Magento order
            $order = $this->_getOrder();

            // Retrieve Mirakl connection
            $connection = $this->_getConnection($order->getMiraklConnectionId());

            Mage::register('current_order', $order);
            Mage::register('mirakl_seller_connection', $connection);

            // Retrieve Mirakl thread
            if ($threadId = $this->getRequest()->getParam('thread_id')) {
                $miraklThread = $this->_getMiraklThread($connection, $threadId);
                Mage::register('mirakl_seller_thread', $miraklThread);
            }

            $html = $this->getLayout()
                ->createBlock('mirakl_seller_sales/adminhtml_sales_order_view_thread_view')
                ->setShowForm(true)
                ->toHtml();
        } catch (Exception $e) {
            $html = $this->getLayout()
                ->getMessagesBlock()
                ->addError($e->getMessage())
                ->toHtml();
        }

        $this->getResponse()->setBody($html);
    }

    /**
     * @param   string  $recipients
     * @return  array
     */
    protected function _getCreateTo($recipients)
    {
        $to = array();

        $addCustomer = ($recipients === 'CUSTOMER' || $recipients === 'BOTH');
        $addOperator = ($recipients === 'OPERATOR' || $recipients === 'BOTH');

        if ($addCustomer) {
            $to[] = 'CUSTOMER';
        }

        if ($addOperator) {
            $to[] = 'OPERATOR';
        }

        return $to;
    }

    /**
     * @param   ThreadDetails   $thread
     * @param   string          $recipients
     * @return  array
     */
    protected function _getReplyTo(ThreadDetails $thread, $recipients)
    {
        $to = array();

        $addCustomer = ($recipients === 'CUSTOMER' || $recipients === 'BOTH');
        $addOperator = ($recipients === 'OPERATOR' || $recipients === 'BOTH');

        /** @var ThreadParticipant $participant */
        foreach ($thread->getAuthorizedParticipants() as $participant) {
            if ($participant->getType() == 'CUSTOMER' && $addCustomer) {
                $to[] = array('type' => 'CUSTOMER', 'id' => $participant->getId());
            } elseif ($participant->getType() == 'OPERATOR' && $addOperator) {
                $to[] = array('type' => 'OPERATOR');
            }
        }

        return $to;
    }
}
