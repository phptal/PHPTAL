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

class TalesStringTest extends PHPUnit_Framework_TestCase {

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
        $rgm = preg_match('/\'\'\s*?\.*\$ctx->name.*?\'\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPathExtended()
    {
        $res = PHPTAL_TalesInternal::string('hello ${user/name} how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->user, \'name\'.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testQuote()
    {
        $tpl = new PHPTAL('input/tales-string-01.html');
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
        $tpl = new PHPTAL('input/tales-string-02.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-string-02.html');
        $this->assertEquals($exp, $res);
    }

    function testEscape()
    {
        $tpl = new PHPTAL('input/tales-string-03.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-string-03.html');
        $this->assertEquals($exp,$res);
    }
}

?>
