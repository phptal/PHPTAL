<?php

/**
 * @package phptal
 */
interface PHPTAL_Source
{
    /** Returns string, unique path identifying the template source. */
    public function getRealPath();
    /** Returns long, the template source last modified time. */
    public function getLastModifiedTime();
    /** Returns string, the template source. */
    public function getData();
}

?>
