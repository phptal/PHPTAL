<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesinski <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: PHPTAL.php 500 2009-03-31 13:05:37Z kornel $
 * @link     http://phptal.motion-twin.com/ 
 */
 
 
require_once PHPTAL_DIR.'PHPTAL/Filter.php';

/**
 * simple filter that removes XML comments
 */
class PHPTAL_CommentFilter implements PHPTAL_Filter
{
    public function filter($src){
        return preg_replace('/(<!--.*?-->)/s', '', $src);
    }
}
