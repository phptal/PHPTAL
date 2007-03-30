<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

require_once 'config.php';
require_once 'PHPTAL/Php/Tales.php';
require_once 'PHPTAL/Tales.php';

function phptal_tales_custom($src,$nothrow)
{
    return 'sprintf("%01.2f", '.PHPTAL_TalesInternal::path($src, $nothrow).')';
}

class MyTalesClass implements PHPTAL_Tales
{
    public static function reverse($exp,$nothrow){
        return 'strrev('.phptal_tales($exp,$nothrow).')';
    }
}

class TalesTest extends PHPUnit_Framework_TestCase 
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
        $this->assertEquals('$ctx->foo->x[10]->doBar()', $res);
    }

    function testPath()
    {
        $src = 'foo/x/y';
        $res = phptal_tales($src);
        $this->assertEquals("phptal_path(\$ctx->foo, 'x/y')", $res);
    }

    function testNot()
    {
        $src = "not: php: foo()";
        $res = phptal_tales($src);
        $this->assertEquals("!(foo())", $res);
    }

    function testTrue()
    {
        $tpl = new PHPTAL('input/tales-true.html');
        $tpl->isNotTrue = false;
        $tpl->isTrue = true;
        $res = $tpl->execute();
        $this->assertEquals(trim_file('output/tales-true.html'), trim_string($res));
    }

    function testCustom()
    {
        $src = 'custom: some/path';
        $this->assertEquals('sprintf("%01.2f", phptal_path($ctx->some, \'path\'))', 
                            phptal_tales($src));
    }

    function testCustomClass()
    {
        $src = 'MyTalesClass.reverse: some';
        $this->assertEquals('strrev($ctx->some)', phptal_tales($src));
    }
}

?>
