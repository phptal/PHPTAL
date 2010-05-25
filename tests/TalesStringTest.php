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
require_once 'PHPTAL/Tales.php';
PHPTAL::restoreIncludePath();

class TalesStringTest extends PHPTAL_TestCase {

    function testSimple()
    {
        $this->assertEquals('\'this is a string\'', PHPTAL_Php_TalesInternal::string('this is a string'));
    }

    function testDoubleDollar()
    {
        $this->assertEquals('\'this is a $string\'', PHPTAL_Php_TalesInternal::string('this is a $$string'));
    }

    function testSubPathSimple()
    {
        $res = PHPTAL_Php_TalesInternal::string('hello $name how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->name.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPath()
    {
        $res = PHPTAL_Php_TalesInternal::string('${name}');
        $this->assertRegExp('/^(\'\'\s*?\.*)?\$ctx->name(.*?\'\')?$/', $res);
    }

    function testSubPathExtended()
    {
        $res = PHPTAL_Php_TalesInternal::string('hello ${user/name} how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->user, \'name\'.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testQuote()
    {
        $tpl = $this->newPHPTAL('input/tales-string-01.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tales-string-01.html');
        $this->assertEquals($exp, $res);
    }

    function testDoubleVar()
    {
        $res = PHPTAL_Php_TalesInternal::string('hello $foo $bar');
        $this->assertEquals(1, preg_match('/ctx->foo/', $res), '$foo not interpolated');
        $this->assertEquals(1, preg_match('/ctx->bar/', $res), '$bar not interpolated');
    }

    function testDoubleDotComa()
    {
        $tpl = $this->newPHPTAL('input/tales-string-02.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tales-string-02.html');
        $this->assertEquals($exp, $res);
    }

    function testEscape()
    {
        $tpl = $this->newPHPTAL('input/tales-string-03.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tales-string-03.html');
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>
            ${string:&lt;foo/&gt;}
            ${structure string:&lt;foo/&gt;}
            <x y="${string:&lt;foo/&gt;}" tal:content="string:&lt;foo/&gt;" />
            <x y="${structure string:&lt;foo/&gt;}" tal:content="structure string:&lt;foo/&gt;" />
        </p>');
        $this->assertEquals(normalize_html('<p>&lt;foo/&gt;<foo/><x y="&lt;foo/&gt;">&lt;foo/&gt;</x><x y="<foo/>"><foo/></x></p>'),
                            normalize_html($tpl->execute()));
    }
}


