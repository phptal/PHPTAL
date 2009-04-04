<?php

/**
 * Fake template source that makes PHPTAL->setString() work
 * 
 * @package PHPTAL
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

