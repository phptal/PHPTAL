<?php

require_once 'config.php';
require_once 'PHPTAL/Tales.php';

class TalesTest extends PHPUnit2_Framework_TestCase 
{
    function testString()
    {
        $src = 'string:foo bar baz';
        $res = phptal_tales($src);
        $this->assertEquals("'foo bar baz'", $res);

        $src = "'foo bar baz'";
        $res = phptal_tales($src);
        $this->assertEquals("'foo bar baz'", $res);
    }

    function testPhp()
    {
        $src = 'php: foo.x[10].doBar()';
        $res = phptal_tales($src);
        $this->assertEquals('$tpl->foo->x[10]->doBar()', $res);
    }

    function testPath()
    {
        $src = 'foo/x/y';
        $res = phptal_tales($src);
        $this->assertEquals("phptal_path(\$tpl, 'foo/x/y')", $res);
    }

    function testNot()
    {
        $src = "not: php: foo()";
        $res = phptal_tales($src);
        $this->assertEquals("!foo()", $res);
    }
}

?>
