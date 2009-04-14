<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: PHPTAL.php 517 2009-04-07 10:56:30Z kornel $
 * @link     http://phptal.motion-twin.com/ 
 */
 
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
