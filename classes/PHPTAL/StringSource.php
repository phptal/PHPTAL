<?php

require_once PHPTAL_DIR.'PHPTAL/Source.php';

/**
 * @package phptal
 */
class PHPTAL_StringSource implements PHPTAL_Source
{
    public function __construct($data, $realpath)
    {
        $this->_data = $data;
        $this->_realpath = $realpath;
    }

    public function getLastModifiedTime()
    {
        if (file_exists($this->_realpath))
            return @filemtime($this->_realpath);
        return 0;
    }

    public function getData()
    {
        return $this->_data;
    }

    public function getRealPath()
    {
        return $this->_realpath;
    }
}

?>
