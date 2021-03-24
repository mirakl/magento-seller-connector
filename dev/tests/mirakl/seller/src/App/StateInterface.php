<?php
namespace Mirakl\App;

interface StateInterface
{
    /**
     * @return  bool
     */
    public function getNeedsConfigReinit();

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setNeedsConfigReinit($flag);

    /**
     * @return  bool
     */
    public function getNeedsFullReindex();

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setNeedsFullReindex($flag);

    /**
     * @return  bool
     */
    public function getNeedsCacheClearing();

    /**
     * @param   bool    $flag
     * @return  $this
     */
    public function setNeedsCacheClearing($flag);
}