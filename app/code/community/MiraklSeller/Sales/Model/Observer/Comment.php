<?php

use Mage_Adminhtml_Controller_Action as Action;

class MiraklSeller_Sales_Model_Observer_Comment extends MiraklSeller_Sales_Model_Observer_Abstract
{
    /**
     * Intercept add comment on order from back office
     *
     * @param   Varien_Event_Observer   $observer
     */
    public function onAddCommentBefore(Varien_Event_Observer $observer)
    {
        if (!$order = $this->_getOrderFromEvent($observer->getEvent())) {
            return; // Do not do anything if it's not an imported Mirakl order
        }

        /** @var Mage_Adminhtml_Sales_OrderController $action */
        $action = $observer->getEvent()->getControllerAction();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = $action->getRequest();
        $history = $request->getParam('history', array('comment' => ''));

        $connection = $this->_getConnectionById($order->getMiraklConnectionId());
        $this->_getMiraklOrder($connection, $order->getMiraklOrderId()); // Just to save the Mirakl order in registry

        if (empty($history['comment']) || empty($history['is_customer_notified'])) {
            return; // Not possible to send empty comment or to send a message to the shop as a seller
        }

        $subject = $this->__('New comment on order %s', $order->getMiraklOrderId());
        $body    = $history['comment'];

        try {
            $this->_apiOrder->createOrderMessage($connection, $order->getMiraklOrderId(), $subject, $body, true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            try {
                $result = \Mirakl\parse_json_response($e->getResponse());
                $this->sendError($action, $result['message']);
            } catch (\InvalidArgumentException $e) {
                $this->sendError($action, $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->sendError($action, $e->getMessage());
        }

        Mage::register('sales_order', $order);

        // Do not save the message in Magento because already saved in Mirakl
        $action->setFlag('', 'no-dispatch', true);
        $action->loadLayout('empty');
        $action->renderLayout();
    }

    /**
     * @param   Action  $action
     * @param   string  $message
     */
    private function sendError(Action $action, $message)
    {
        $response = array(
            'error'   => true,
            'message' => $message,
        );
        $response = json_encode($response);
        $action->getResponse()->setBody($response)->sendResponse();
        $action->getResponse()->clearBody();
        $action->setFlag('', 'no-dispatch', true);
    }
}