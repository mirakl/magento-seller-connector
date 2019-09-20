<?php

class MiraklSeller_Process_Model_Core_Cookie extends Mage_Core_Model_Cookie
{
    /**
     * @return  bool
     */
    private function isMiraklProcessAsync()
    {
        $request = Mage::app()->getRequest();

        return $request->getRouteName() === 'adminhtml'
            && $request->getControllerName() == 'mirakl_seller_process'
            && $request->getActionName() == 'async';
    }

    /**
     * {@inheritdoc}
     */
    public function renew($name, $period = null, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        if (!$this->isMiraklProcessAsync()) {
            parent::renew($name, $period, $path, $domain, $secure, $httponly);
        }

        return $this;
    }
}