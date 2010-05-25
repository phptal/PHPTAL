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


function phptal_tales_custom($src, $nothrow)
{
    return 'sprintf("%01.2f", '.PHPTAL_Php_TalesInternal::path($src, $nothrow).')';
}

class MyTalesClass implements PHPTAL_Tales
{
    public static function reverse($exp,$nothrow){
        return 'strrev('.phptal_tales($exp, $nothrow).')';
    }
}

class TalesTest extends PHPTAL_TestCase
{
    function testString()
    {
        $src = 'string:foo bar baz';
        $res = PHPTAL_Php_TalesInternal::compileToPHPStatements($src);
        $this->assertEquals("'foo bar baz'", $res);

        $src = "'foo bar baz'";
        $res = phptal_tales($src);
        $this->assertEquals("'foo bar baz'", $res);
    }

    function testPhp()
    {
        $src = 'php: foo.x[10].doBar()';
        $res = PHPTAL_Php_TalesInternal::compileToPHPStatements($src);
        $this->assertEquals('$ctx->foo->x[10]->doBar()', $res);
    }

    function testPath()
    {
        $src = 'foo/x/y';
        $res = phptal_tales($src);
        $this->assertEquals("\$ctx->path(\$ctx->foo, 'x/y')", $res);
    }

    function testNot()
    {
        $src = "not: php: foo()";
        $res = PHPTAL_Php_TalesInternal::compileToPHPStatements($src);
        $this->assertEquals("!(foo())", $res);
    }

    function testNotVar()
    {
        $src = "not:foo";
        $res = PHPTAL_Php_TalesInternal::compileToPHPStatements($src);
        $this->assertEquals('!($ctx->foo)', $res);
    }

    function testNotPath()
    {
        $src = "not:foo/bar/baz";
        $res = PHPTAL_Php_TalesInternal::compileToPHPStatements($src);
        $this->assertEquals('!($ctx->path($ctx->foo, \'bar/baz\'))', $res);
    }

    function testTrue()
    {
        $tpl = $this->newPHPTAL('input/tales-true.html');
        $tpl->isNotTrue = false;
        $tpl->isTrue = true;
        $res = $tpl->execute();
        $this->assertEquals(normalize_html_file('output/tales-true.html'), normalize_html($res));
    }

    function testCustom()
    {
        $src = 'custom: some/path';
        $this->assertEquals('sprintf("%01.2f", $ctx->path($ctx->some, \'path\'))',
                            phptal_tales($src));
    }

    function testCustomClass()
    {
        $src = 'MyTalesClass.reverse: some';
        $this->assertEquals('strrev($ctx->some)', phptal_tales($src));
    }

    function testTaleNeverReturnsArray()
    {
        $this->assertType('string', phptal_tale('foo | bar | baz | nothing'));
    }

    function testTalesReturnsArray()
    {
        $this->assertType('array', phptal_tales('foo | bar | baz | nothing'));
    }

    function testInterpolate1()
    {
        $this->assertEquals('$ctx->{$ctx->path($ctx->some, \'path\')}', PHPTAL_Php_TalesInternal::compileToPHPStatements('${some/path}'));
    }

    function testInterpolate2()
    {
        $this->assertEquals('$ctx->path($ctx->{$ctx->path($ctx->some, \'path\')}, \'meh\')', phptal_tales('${some/path}/meh'));
    }

    function testInterpolate3()
    {
        $this->assertEquals('$ctx->path($ctx->meh, $ctx->path($ctx->some, \'path\'))', PHPTAL_Php_TalesInternal::compileToPHPStatements('meh/${some/path}'));
    }

    function testInterpolate4()
    {
        $this->assertEquals('$ctx->path($ctx->{$ctx->meh}, $ctx->blah)', phptal_tales('${meh}/${blah}'));
    }

    function testSuperglobals()
    {
        $this->assertEquals('$ctx->path($ctx->{\'_GET\'}, \'a\')', PHPTAL_Php_TalesInternal::compileToPHPStatements('_GET/a'));
    }

    function testInterpolatedPHP1()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div tal:content="string:foo${php:true?&apos;bar&apos;:0}${php:false?0:\'b$$a$z\'}"/>');
        $this->assertEquals('<div>foobarb$$a$z</div>', $tpl->execute());
    }

    function testInterpolatedTALES()
    {
        $tpl = $this->newPHPTAL();
        $tpl->var = 'ba';
        $tpl->setSource('<div tal:content="string:foo${nonexistant | string:bar$var}z"/>');
        $this->assertEquals('<div>foobarbaz</div>', $tpl->execute());
    }

    function testInterpolatedPHP2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->somearray = array(1=>9, 9, 9);
        $tpl->setSource('<div tal:repeat="x php:somearray"><x tal:replace=\'repeat/${php:
            "x"}/key\'/></div>');
        $this->assertEquals('<div>1</div><div>2</div><div>3</div>', $tpl->execute());
    }

    function testStringWithLongVarName()
    {
        $tpl = $this->newPHPTAL();
        $tpl->aaaaaaaaaaaaaaaaaaaaa = 'ok';
        $tpl->bbb = 'ok';

        $tpl->setSource('<x tal:attributes="y string:$bbb/y/y; x string:$aaaaaaaaaaaaaaaaaaaaa/x/x" />');
        $tpl->execute();
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testForbidsStatementsInCustomModifiers()
    {
        $tpl = $this->newPHPTAL();

        $tpl->setSource('<x tal:content="testmodifier:foo"/>')->execute();
    }


    /**
     * @expectedException PHPTAL_ParserException
     */
    function testThrowsInvalidPath()
    {
        phptal_tales("I am not valid expression");
    }

    function testThrowsUnknownModifier()
    {
        try
        {
            phptal_tales('testidontexist:foo');
            $this->fail();
        }
        catch(PHPTAL_UnknownModifierException $e)
        {
            $this->assertEquals('testidontexist', $e->getModifierName());
        }
    }
}

function phptal_tales_testmodifier($expr, $nothrow)
{
    return 'print("test");';
}
