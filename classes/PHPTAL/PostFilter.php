<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */

/**
 * Base class for postfilters. You should extend this class and override methods you're interested in.
 *
 * @package PHPTAL
 */
abstract class PHPTAL_PostFilter implements PHPTAL_Filter
{
    private $phptal;
    /**
     * Set which instance of PHPTAL is using this filter.
     * 
     * @param PHPTAL $phptal instance
     */
    final function setPHPTAL(PHPTAL $phptal)
    {
        $this->phptal = $phptal;
    }

    /**
     * Returns PHPTAL class instance that is currently using this postfilter.
     * 
     * @return PHPTAL
     */
    final protected function getPHPTAL()
    {
        return $this->phptal;
    }

    /**
     * Receives generated markup fragment that had phptal:filter attribute calling this filter.
     * Postfilters are called every time template is executed, and should be as fast as possible.
     *
     * Default implementation calls filter(). Override it.
     *
     * @param string $src markup fragment to filter
     *
     * @return string
     */
    public function filterFragment($src)
    {
        return $this->filter($src);
    }

    /**
     * Receives generated markup and is expected to return modified markup.
     * Postfilters are called very time template is executed, and should be as fast as possible.
     *
     * Default implementation does nothing. Override it.
     *
     * @param string $src template output to filter
     *
     * @return string
     */
    public function filter($src)
    {
        return $src;
    }
}


