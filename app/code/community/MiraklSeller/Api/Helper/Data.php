<?php

class MiraklSeller_Api_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Formats given size (in bytes) into an easy readable size
     *
     * @param   int     $size
     * @param   string  $separator
     * @return  string
     */
    public function formatSize($size, $separator = ' ')
    {
        $unit = array('bytes', 'kb', 'mb', 'gb', 'tb', 'pb');
        $size = round($size / pow(1024, ($k = (int) (floor(log($size, 1024))))), 2) . $separator . $unit[$k];

        return $size;
    }
}
