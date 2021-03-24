<?php

use Mirakl\MMP\Common\Domain\Collection\Message\Thread\ThreadParticipantCollection;
use Mirakl\MMP\Common\Domain\Message\Thread\Thread;
use Mirakl\MMP\Common\Domain\Message\Thread\ThreadParticipant;
use Mirakl\MMP\Common\Domain\Reason\ReasonType;
use MiraklSeller_Api_Model_Connection as Connection;

class MiraklSeller_Core_Helper_Thread extends Mage_Core_Helper_Data
{
    /**
     * @param   Thread  $thread
     * @param   array   $excludeTypes
     * @return  array
     */
    public function getThreadCurrentParticipantsNames(Thread $thread, array $excludeTypes = array())
    {
        return $this->getThreadParticipantNames($thread->getCurrentParticipants(), $excludeTypes);
    }

    /**
     * @param   Thread  $thread
     * @param   array   $excludeTypes
     * @return  array
     */
    public function getThreadAuthorizedParticipantsNames(Thread $thread, array $excludeTypes = array())
    {
        return $this->getThreadParticipantNames($thread->getAuthorizedParticipants(), $excludeTypes);
    }

    /**
     * @param   ThreadParticipantCollection $participants
     * @param   array                       $excludeTypes
     * @return  array
     */
    public function getThreadParticipantNames(ThreadParticipantCollection $participants, array $excludeTypes = array())
    {
        $participantsNames = array();

        /** @var ThreadParticipant $participant */
        foreach ($participants as $participant) {
            if (!empty($excludeTypes) && in_array($participant->getType(), $excludeTypes)) {
                continue;
            }
            $participantsNames[$participant->getType()] = $participant->getDisplayName();
        }

        return $participantsNames;
    }

    /**
     * @param   Connection  $connection
     * @param   Thread      $thread
     * @return  string
     */
    public function getThreadTopic(Connection $connection, Thread $thread)
    {
        $thread = $thread->toArray();

        if (!isset($thread['topic']['type']) || !isset($thread['topic']['value'])) {
            return '';
        }

        $topicValue = $thread['topic']['value'];

        if ($thread['topic']['type'] == 'REASON_CODE') {
            /** @var \Mirakl\MMP\Shop\Domain\Reason $reason */
            $locale = Mage::helper('mirakl_seller/config')->getLocale();
            $reasons = Mage::helper('mirakl_seller_api/reason')
                ->getTypeReasons($connection, ReasonType::ORDER_MESSAGING, $locale);
            foreach ($reasons as $reason) {
                if ($reason->getCode() == $topicValue) {
                    return $reason->getLabel();
                }
            }
        }

        return $topicValue;
    }
}
