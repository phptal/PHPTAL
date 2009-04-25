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

require_once dirname(__FILE__)."/config.php";

PHPTAL::setIncludePath();
require_once 'PHPTAL/CommentFilter.php';
PHPTAL::restoreIncludePath();

class CommentFilterTest extends PHPTAL_TestCase
{
	function testIt(){
		$t = $this->newPHPTAL('input/comment-filter-01.html');
		$t->setPreFilter(new PHPTAL_CommentFilter());
		$res = $t->execute();
		$res = trim_string($res);
		$exp = trim_file('output/comment-filter-01.html');
		$this->assertEquals($exp,$res);
	}
}
