<?php
namespace Mirakl\Fixture;

interface FixturesLoaderInterface
{
    /**
     * @param   string  $file
     * @return  void
     */
    public function load($file);
}