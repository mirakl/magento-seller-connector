<?php
namespace Mirakl\Fixture\System;

use Mirakl\Fixture\AbstractFixturesLoader;

class ConfigLoader extends AbstractFixturesLoader
{
    /**
     * {@inheritdoc}
     */
    public function load($file)
    {
        $config = $this->_getJsonFileContents($file);
        foreach ($config as $data) {
            if (!isset($data['path']) || !isset($data['value'])) {
                throw new \InvalidArgumentException('The fields "path" and "value" are required');
            }

            $path = $data['path'];
            $value = $data['value'];
            $scope = $data['scope'] ?? 'default';
            $scopeId = $data['scope_id'] ?? '0';

            if ($value != \Mage::getStoreConfig($path, $scopeId)) {
                \Mage::getConfig()->saveConfig($path, $value, $scope, $scopeId);
                $this->_appState->setNeedsConfigReinit(true);
            }
        }
    }
}