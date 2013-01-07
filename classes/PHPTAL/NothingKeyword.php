<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author Andrew Crites <explosion-pills@aysites.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */


/**
 * Representation of the template 'nothing' keyword
 *
 * @package PHPTAL
 * @subpackage Keywords
 */
class PHPTAL_NothingKeyword implements PHPTAL_Keywords
{
    public function __toString()
    {
        return 'null';
    }

    public function count()
    {
        return 0;
    }

    public function jsonSerialize()
    {
        return null;
    }
}
?>
