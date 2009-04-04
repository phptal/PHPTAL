<?php

/**
 * You can implement this interface to load templates from various sources (see SourceResolver)
 * 
 * @package PHPTAL
 */
interface PHPTAL_Source
{
    /** 
     * unique path identifying the template source. 
     * 
     * @return string
     */
    public function getRealPath();
    
    /** 
     * template source last modified time (unix timestamp)
     * Return 0 if unknown
     *
     * @return long
     */
    public function getLastModifiedTime();
    
    /** 
     * the template source
     * 
     * @return string
     */
    public function getData();
}
