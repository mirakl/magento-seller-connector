<?php

use Mirakl\MMP\Common\Domain\Collection\Message\OrderMessageCollection;
use Mirakl\MMP\Common\Domain\Message\OrderMessage;
use Mirakl\MMP\Common\Domain\UserType;
use Mirakl\MMP\Shop\Domain\Order\ShopOrder;

class MiraklSeller_Sales_Block_Adminhtml_Sales_Order_Messages extends Mage_Adminhtml_Block_Template
{
    /**
     * @var string
     */
    protected $_template = 'mirakl_seller/sales/order/messages.phtml';

    /**
     * @var MiraklSeller_Api_Model_Connection
     */
    protected $_connection;

    /**
     * @return  OrderMessageCollection
     */
    public function getAllOrderMessages()
    {
        $messages = $this->getMiraklOrderMessages();

        // Merge Mirakl messages with Magento comments
        foreach ($this->getOrder()->getStatusHistoryCollection(true) as $comment) {
            if (!$comment->getComment()) {
                continue;
            }

            $createdAt = new \DateTime();
            $createdAt->setTimestamp($comment->getCreatedAtDate()->getTimestamp());
            $messages->add(
                array(
                    'source'       => 'magento',
                    'date_created' => $createdAt,
                    'subject'      => '',
                    'body'         => $comment->getComment(),
                    'user_sender'  => array(
                        'name' => $this->getConnection()->getName(),
                        'type' => UserType::SHOP,
                    ),
                )
            );
        }

        // Sort messages by creation date
        $items = $messages->getItems();
        usort(
            $items, function (OrderMessage $a, OrderMessage $b) {
                return $a->getDateCreated() <= $b->getDateCreated() ? 1 : -1;
            }
        );

        $messages->setItems($items);

        return $messages;
    }

    /**
     * @return  MiraklSeller_Api_Model_Connection
     */
    public function getConnection()
    {
        if (null === $this->_connection) {
            $connectionId = $this->getOrder()->getMiraklConnectionId();
            $this->_connection = Mage::getModel('mirakl_seller_api/connection')->load($connectionId);
        }

        return $this->_connection;
    }

    /**
     * @return  Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('sales_order');
    }

    /**
     * @return  ShopOrder
     */
    public function getMiraklOrder()
    {
        return Mage::registry('mirakl_order');
    }

    /**
     * @return  OrderMessageCollection
     */
    public function getMiraklOrderMessages()
    {
        if ($connection = $this->getConnection()) {
            return Mage::helper('mirakl_seller_api/order')->getOrderMessages($connection, $this->getMiraklOrder());
        }

        return new OrderMessageCollection();
    }

    /**
     * Builds the sender name of the specified order message
     *
     * @param   OrderMessage    $_message
     * @return  string
     */
    public function getSenderName(OrderMessage $_message)
    {
        return $_message->getUserSender()->getName();
    }

    /**
     * Returns true if the given message was sent by the customer
     *
     * @param   OrderMessage    $message
     * @return  bool
     */
    public function isCustomerMessage(OrderMessage $message)
    {
        return $message->getUserSender()->getType() == UserType::CUSTOMER;
    }

    /**
     * Returns true if the given message was sent by the shop
     *
     * @param   OrderMessage    $message
     * @return  bool
     */
    public function isShopMessage(OrderMessage $message)
    {
        return $message->getUserSender()->getType() == UserType::SHOP;
    }
}