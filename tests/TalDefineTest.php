<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
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
require_once 'PHPTAL.php';
require_once 'PHPTAL/Attribute.php';
require_once 'PHPTAL/Attribute/TAL/Define.php';

class TalDefineTest extends PHPUnit2_Framework_TestCase 
{
    function testExpressionParser()
    {
        $att = PHPTAL_Attribute::createAttribute(null, 'tal:define', 'a b');
        
        list($defineScope, $defineVar, $expression) = $att->parseExpression('local a_234z b');
        $this->assertEquals('local', $defineScope);
        $this->assertEquals('a_234z', $defineVar);
        $this->assertEquals('b', $expression);

        list($defineScope, $defineVar, $expression) = $att->parseExpression('global a_234z b');
        $this->assertEquals('global', $defineScope);
        $this->assertEquals('a_234z', $defineVar);
        $this->assertEquals('b', $expression); 

        list($defineScope, $defineVar, $expression) = $att->parseExpression('a_234Z b');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('a_234Z', $defineVar);
        $this->assertEquals('b', $expression); 

        list($defineScope, $defineVar, $expression) = $att->parseExpression('a');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('a', $defineVar);
        $this->assertEquals(false, $expression); 

        list($defineScope, $defineVar, $expression) = $att->parseExpression('global a string: foo; bar; baz');
        $this->assertEquals('global', $defineScope);
        $this->assertEquals('a', $defineVar);
        $this->assertEquals('string: foo; bar; baz', $expression); 


        list($defineScope, $defineVar, $expression) = $att->parseExpression('foo this != other');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('foo', $defineVar);
        $this->assertEquals('this != other', $expression); 

        list($defineScope, $defineVar, $expression) = $att->parseExpression('x exists: a | not: b | path: c | 128');
        $this->assertEquals(false, $defineScope);
        $this->assertEquals('x', $defineVar);
        $this->assertEquals('exists: a | not: b | path: c | 128', $expression);
    }

    function testMulti()
    {
        $tpl = new PHPTAL('input/tal-define.01.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.01.html');
        $this->assertEquals($exp, $res);
    }

    function testBuffered()
    {
        $tpl = new PHPTAL('input/tal-define.02.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testMultiChained()
    {
        $tpl = new PHPTAL('input/tal-define.03.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.03.html');
        $this->assertEquals($exp, $res);        
    }

    function testDefineZero()
    {
        $tpl = new PHPTAL('input/tal-define.04.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.04.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineZeroTalesPHP()
    {
        $tpl = new PHPTAL('input/tal-define.05.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.05.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineInMacro()
    {
        $tpl = new PHPTAL('input/tal-define.06.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.06.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineDoNotStealOutput()
    {
        $tpl = new PHPTAL('input/tal-define.07.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.07.html');
        $this->assertEquals($exp, $res);
    }

    function testDefineWithRepeatAndContent()
    {
        $tpl = new PHPTAL('input/tal-define.08.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-define.08.html');
        $this->assertEquals($exp, $res);
    }
}

?>
