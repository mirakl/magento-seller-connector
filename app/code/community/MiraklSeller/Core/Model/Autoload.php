<?php

class MiraklSeller_Core_Model_Autoload
{
    /**
     * @var bool
     */
    protected static $_registered = false;

    /**
     * @var string
     */
    protected $_packageDir;

    /**
     * @return  string
     */
    public function getPackagesDir()
    {
        if (null !== $this->_packageDir) {
            return $this->_packageDir;
        }

        $candidates = new Varien_Object(
            array(
                'candidates' => array(
                    BP . DS . 'vendor',
                    BP . DS . 'includes' . DS . 'mirakl',
                    BP . DS . 'lib' . DS . 'mirakl',
                ),
            )
        );

        Mage::dispatchEvent('mirakl_seller_packages_dir_candidates', array('candidates' => $candidates));

        foreach ($candidates->getData('candidates') as $dir) {
            if (is_dir($dir . DS . 'mirakl')) {
                $this->_packageDir = $dir;
                break;
            }
        }

        return $this->_packageDir;
    }

    /**
     * Register Composer autoloader
     */
    public function registerAutoload()
    {
        if (!self::$_registered) {
            self::$_registered = true;

            if (!is_dir($this->getPackagesDir() . DS . 'composer')) {
                Mage::throwException('Could not find Mirakl SDK library. Please verify your Mirakl Connector installation.');
            }

            if (is_dir($this->getPackagesDir() . DS . 'composer')) {
                $autoloader = $this->getPackagesDir() . DS . 'autoload.php';
                if (file_exists($autoloader)) {
                    require_once $autoloader;
                }
            }
        }
    }
}
