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
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */


require_once 'PHPTAL/Filter.php';

/**
 * simple filter that removes XML comments
 */
class PHPTAL_CommentFilter implements PHPTAL_Filter
{
    public function filter($src)
    {
        return preg_replace('/(<!--.*?-->)/s', '', $src);
    }
}
