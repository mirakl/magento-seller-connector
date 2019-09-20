<?php
namespace Mirakl\Fixture;

use Mirakl\App\StateInterface;

abstract class AbstractFixturesLoader implements FixturesLoaderInterface
{
    /**
     * @var StateInterface
     */
    protected $_appState;

    /**
     * @param   StateInterface  $appState
     */
    public function __construct(StateInterface $appState)
    {
        $this->_appState = $appState;
    }

    /**
     * @param   string  $file
     * @return  string
     */
    protected function _getFileContents($file)
    {
        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf("File '%s' could not be found.", $file));
        }

        return file_get_contents($file);
    }

    /**
     * @param   string  $file
     * @param   bool    $assoc
     * @return  mixed
     */
    protected function _getJsonFileContents($file, $assoc = true)
    {
        return json_decode($this->_getFileContents($file), $assoc);
    }
}