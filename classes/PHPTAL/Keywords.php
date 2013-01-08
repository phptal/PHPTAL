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
 * Interface for template keywords
 *
 * @package PHPTAL
 * @subpackage Keywords
 */
interface PHPTAL_Keywords extends Countable
{
    public function __toString();
}
?>
