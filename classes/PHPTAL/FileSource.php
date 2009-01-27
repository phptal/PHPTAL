<?php

require_once PHPTAL_DIR.'PHPTAL/Source.php';
require_once PHPTAL_DIR.'PHPTAL/SourceResolver.php';

/** 
 * @package phptal
 */
class PHPTAL_FileSource implements PHPTAL_Source
{
    public function __construct($path)
    {
        $this->_path = realpath($path);
        if ($this->_path === false) throw new PHPTAL_IOException("Unable to normalize path '$path'");
    }

    public function getRealPath()
    {
        return $this->_path;
    }

    public function getLastModifiedTime()
    {
        return filemtime($this->_path);
    }

    public function getData()
    {
        return file_get_contents($this->_path);
    }

    private $_path;
}

/** 
 * @package phptal
 */
class PHPTAL_FileSourceResolver implements PHPTAL_SourceResolver
{
    public function __construct($repositories)
    {
        $this->_repositories = $repositories;
    }

    public function resolve($path)
    {
        foreach ($this->_repositories as $repository){
            $file = $repository . DIRECTORY_SEPARATOR . $path;
            if (file_exists($file)){
                return new PHPTAL_FileSource($file);
            }
        }

        if (file_exists($path)){
            return new PHPTAL_FileSource($path);
        }

        return null;
    }

    private $_repositories;
}

?>
