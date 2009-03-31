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
 * @link     http://phptal.motion-twin.com/ 
 */
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

class TalesStringTest extends PHPTAL_TestCase {

    function testSimple()
    {
        $this->assertEquals('\'this is a string\'', PHPTAL_TalesInternal::string('this is a string'));
    }

    function testDoubleDollar()
    {
        $this->assertEquals('\'this is a $string\'', PHPTAL_TalesInternal::string('this is a $$string'));
    }

    function testSubPathSimple()
    {
        $res = PHPTAL_TalesInternal::string('hello $name how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->name.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPath()
    {
        $res = PHPTAL_TalesInternal::string('${name}');
        $this->assertRegExp('/^(\'\'\s*?\.*)?\$ctx->name(.*?\'\')?$/', $res);
    }

    function testSubPathExtended()
    {
        $res = PHPTAL_TalesInternal::string('hello ${user/name} how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->user, \'name\'.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testQuote()
    {
        $tpl = $this->newPHPTAL('input/tales-string-01.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-string-01.html');
        $this->assertEquals($exp, $res);
    }

    function testDoubleVar()
    {
        $res = PHPTAL_TalesInternal::string('hello $foo $bar');
        $this->assertEquals(1, preg_match('/ctx->foo/', $res), '$foo not interpolated');
        $this->assertEquals(1, preg_match('/ctx->bar/', $res), '$bar not interpolated');
    }

    function testDoubleDotComa()
    {
        $tpl = $this->newPHPTAL('input/tales-string-02.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-string-02.html');
        $this->assertEquals($exp, $res);
    }

    function testEscape()
    {
        $tpl = $this->newPHPTAL('input/tales-string-03.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-string-03.html');
        $this->assertEquals($exp,$res);
    }
}

?>
