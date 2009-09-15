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
 * @version  SVN: $Id: Filter.php 576 2009-04-24 10:11:33Z kornel $
 * @link     http://phptal.org/
 */

/**
 * Objects passed to PHPTAL::setPre/PostFilter() must implement this interface
 *
 * @package PHPTAL
 */
class PHPTAL_DomPreFilter
{
    private $phptal;
    function setPHPTAL(PHPTAL $phptal)
    {
        $this->phptal = $phptal; 
    }
    
    function getOutputMode()
    {
        return $this->phptal->getOutputMode();
    }
    
    /**
     * In prefilter it gets template source file and is expected to return new source.
     * Prefilters are called only once before template is compiled, so they can be slow.
     *
     * In postfilter template output is passed to this method, and final output goes to the browser.
     * TAL or PHP tags won't be executed. Postfilters should be fast.
     * 
     * @param PHPTAL_Dom_Element $root PHPTAL's DOM node to modify in place
     * @return void
     */
    public function filterDOM(PHPTAL_Dom_Element $root) {}
    
    /**
     * In prefilter it gets template source file and is expected to return new source.
     * Prefilters are called only once before template is compiled, so they can be slow.
     *
     */
    public function filter($src) {return $src;}
}


