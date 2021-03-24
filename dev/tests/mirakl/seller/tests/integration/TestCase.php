<?php
namespace Mirakl\Test\Integration;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Do the tests need fixtures to be loaded or not
     *
     * @var bool
     */
    protected $_needFixtures = true;

    /**
     * @throws \PHPUnit\Framework\SkippedTestError
     */
    protected function assertPreConditions()
    {
        if ($this->_needFixtures && !\Mage::getStoreConfigFlag('mirakl/tests/ready')) {
            $this->markTestSkipped("Please import fixtures before running integration tests:\n\nphp -f fixtures.php");
        }
    }

    /**
     * @param   string  $fileName
     * @return  bool|string
     */
    protected function _getFileContents($fileName)
    {
        return file_get_contents($this->_getFilePath($fileName));
    }

    /**
     * @return  string
     */
    protected function _getFilesDir()
    {
        return realpath(dirname((new \ReflectionClass(static::class))->getFileName()) . '/_files');
    }

    /**
     * @param   string  $file
     * @return  string
     */
    protected function _getFilePath($file)
    {
        return $this->_getFilesDir() . '/' . $file;
    }

    /**
     * @param   string  $fileName
     * @param   bool    $assoc
     * @return  array
     */
    protected function _getJsonFileContents($fileName, $assoc = true)
    {
        return json_decode($this->_getFileContents($fileName), $assoc);
    }
}