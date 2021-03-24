<?php

class MiraklSeller_Process_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Format specified duration (in seconds) into human readable duration
     *
     * @param   int|DateInterval    $duration
     * @return  string
     */
    public function formatDuration($duration)
    {
        if (!$duration) {
            return '';
        }

        if ($duration instanceof DateInterval) {
            $days    = $duration->d;
            $hours   = $duration->h;
            $minutes = $duration->i;
            $seconds = $duration->s;
        } else {
            $days      = floor($duration / 86400);
            $duration -= $days * 86400;
            $hours     = floor($duration / 3600);
            $duration -= $hours * 3600;
            $minutes   = floor($duration / 60);
            $seconds   = floor($duration - $minutes * 60);
        }

        $duration = '';
        if ($days > 0) {
            $duration .= $this->__('%sd', $days) . ' ';
        }

        if ($hours > 0) {
            $duration .= $this->__('%sh', $hours) . ' ';
        }

        if ($minutes > 0) {
            $duration .= $this->__('%sm', $minutes) . ' ';
        }

        if ($seconds > 0) {
            $duration .= $this->__('%ss', $seconds);
        }

        return trim($duration);
    }

    /**
     * Formats given size (in bytes) into an easy readable size
     *
     * @param   int     $size
     * @param   string  $separator
     * @return  string
     */
    public function formatSize($size, $separator = ' ')
    {
        return Mage::helper('mirakl_seller_api')->formatSize($size, $separator);
    }

    /**
     * Returns number of seconds between now and given date, formatted into readable duration if needed
     *
     * @param   mixed   $date
     * @param   bool    $toDuration
     * @return  int|string
     */
    public function getMoment($date, $toDuration = true)
    {
        if (is_string($date)) {
            $date = new Zend_Date($date, Varien_Date::DATETIME_INTERNAL_FORMAT);
        }

        $now = new Zend_Date(Varien_Date::now(), Varien_Date::DATETIME_INTERNAL_FORMAT);
        $seconds = $now->sub($date)->toValue();

        return $toDuration ? $this->formatDuration($seconds) : $seconds;
    }

    /**
     * @return  string|false
     */
    public function getArchiveDir()
    {
        $path = implode(DS, array('mirakl', 'process', date('Y'), date('m'), date('d')));
        $path = Mage::getConfig()->getOptions()->getMediaDir() . DS . $path;
        if (!Mage::getConfig()->createDirIfNotExists($path)) {
            return false;
        }

        return $path;
    }

    /**
     * Returns URL to the specified file
     *
     * @param   string  $filePath
     * @return  string
     */
    public function getFileUrl($filePath)
    {
        $relativePath = $this->getRelativePath($filePath);

        return dirname(Mage::getBaseUrl('media')) . '/' . $relativePath;
    }

    /**
     * Returns the older pending process
     *
     * @return  null|MiraklSeller_Process_Model_Process
     */
    public function getPendingProcess()
    {
        $process = null;

        // Retrieve processing processes
        $processing = Mage::getModel('mirakl_seller_process/process')
            ->getCollection()
            ->addProcessingFilter();

        // Retrieve pending processes
        $pending = Mage::getModel('mirakl_seller_process/process')
            ->getCollection()
            ->addPendingFilter()
            ->addExcludeHashFilter($processing->getColumnValues('hash'))
            ->addParentCompletedFilter()
            ->setOrder('id', 'ASC'); // oldest first

        $pending->getSelect()->limit(1);

        if ($pending->count()) {
            $process = $pending->getFirstItem();
        }

        return $process;
    }

    /**
     * Removes base dir from specified file path
     *
     * @param   string  $filePath
     * @return  string
     */
    public function getRelativePath($filePath)
    {
        $baseDir = Mage::getConfig()->getOptions()->getBaseDir();

        return trim(str_replace($baseDir, '', $filePath), DS);
    }

    /**
     * Archives specified file in media/ folder
     *
     * @param   string|SplFileObject    $file
     * @return  string|false
     */
    public function saveFile($file)
    {
        if (!$path = $this->getArchiveDir()) {
            return false;
        }

        if (is_string($file)) {
            $file = new SplFileObject($file, 'r');
        }

        list ($micro, $time) = explode(' ', microtime());
        $filename = sprintf(
            '%s_%s.%s',
            date('Ymd_His', $time),
            $micro,
            $file->getFlags() & SplFileObject::READ_CSV ? 'csv' : 'txt'
        );
        $filepath = $path . DS . $filename;

        if (!$fh = @fopen($filepath, 'w+')) {
            return false;
        }

        $file->rewind();
        while (!$file->eof()) {
            fwrite($fh, $file->fgets());
        }

        fclose($fh);

        return $filepath;
    }
}
