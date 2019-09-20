<?php

class MiraklSeller_Core_Helper_Data extends Mage_Core_Helper_Data
{
    /**
     * @param   DateTime    $date
     * @param   string      $format
     * @param   bool        $showTime
     * @return  string
     */
    public function formatDateTime(\DateTime $date, $format = 'medium', $showTime = true)
    {
        return $this->formatDate(\Mirakl\date_format($date), $format, $showTime);
    }

    /**
     * Returns current version of the Magento Seller Connector
     *
     * @return  string
     */
    public function getVersion()
    {
        $moduleName = $this->_getModuleName();

        /** @var Mage_Core_Model_Config_Element $element */
        $element = Mage::getConfig()->getModuleConfig($moduleName)->version;

        return strval($element->getAttribute('name') ?: $element);
    }

    /**
     * Returns current version of the PHP SDK used by the Magento Connector
     *
     * @return  string
     */
    public function getVersionSDK()
    {
        $matches = array();
        $packages = array('sdk-php-shop', 'sdk-php'); // try different package names
        $packagesDir = Mage::getModel('mirakl_seller/autoload')->getPackagesDir();

        foreach ($packages as $package) {
            $file = implode(DS, array($packagesDir, 'mirakl', $package, 'composer.json'));
            if (file_exists($file)) {
                preg_match('#"version":\s+"(\d+\.\d+\.\d+-?.*)"#', file_get_contents($file), $matches);
            }
        }

        return isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * Returns true if specified $date is valid compared to specified date range (from => to)
     *
     * @param   string          $from
     * @param   string          $to
     * @param   \DateTime|null  $date
     * @return  bool
     */
    public function isDateValid($from, $to, \DateTime $date = null)
    {
        if (!$from && !$to) {
            return true;
        }

        $currentDate = null !== $date ? $date : new \DateTime('today');
        $fromDate    = new \DateTime($from);
        $toDate      = new \DateTime($to);

        if (!$from) {
            $isValid = $currentDate <= $toDate;
        } elseif (!$to) {
            $isValid = $currentDate >= $fromDate;
        } else {
            $isValid = $currentDate >= $fromDate && $currentDate <= $toDate;
        }

        return $isValid;
    }
}
