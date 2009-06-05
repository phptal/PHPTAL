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

class DoctypeTest extends PHPTAL_TestCase
{
	function testSimple()
	{
		$tpl = $this->newPHPTAL('input/doctype.01.html');
		$res = $tpl->execute();
		$res = trim_string($res);
		$exp = trim_file('output/doctype.01.html');
		$this->assertEquals($exp, $res);
	}

	function testMacro()
	{
		$tpl = $this->newPHPTAL('input/doctype.02.user.html');
		$res = $tpl->execute();
		$res = trim_string($res);
		$exp = trim_file('output/doctype.02.html');
		$this->assertEquals($exp, $res);
	}

	function testDeepMacro()
	{
		$tpl = $this->newPHPTAL('input/doctype.03.html');
		$res = $tpl->execute();
		$res = trim_string($res);
		$exp = trim_file('output/doctype.03.html');
		$this->assertEquals($exp, $res);
	}

	function testDtdInline()
	{
		$tpl = $this->newPHPTAL('input/doctype.04.html');
		$res = $tpl->execute();
		$res = trim_string($res);
		$exp = trim_file('output/doctype.04.html');
		$this->assertEquals($exp, $res);
	}
}


