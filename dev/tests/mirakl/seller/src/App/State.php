<?php
namespace Mirakl\App;

class State extends \Varien_Object implements StateInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNeedsConfigReinit()
    {
        return $this->getDataSetDefault('needs_config_reinit', false);
    }

    /**
     * {@inheritdoc}
     */
    public function setNeedsConfigReinit($flag)
    {
        $this->setData('needs_config_reinit', (bool) $flag);
    }

    /**
     * {@inheritdoc}
     */
    public function getNeedsFullReindex()
    {
        return $this->getDataSetDefault('needs_full_reindex', false);
    }

    /**
     * {@inheritdoc}
     */
    public function setNeedsFullReindex($flag)
    {
        $this->setData('needs_full_reindex', (bool) $flag);
    }

    /**
     * {@inheritdoc}
     */
    public function getNeedsCacheClearing()
    {
        return $this->getDataSetDefault('needs_cache_clearing', false);
    }

    /**
     * {@inheritdoc}
     */
    public function setNeedsCacheClearing($flag)
    {
        $this->setData('needs_cache_clearing', (bool) $flag);
    }
}