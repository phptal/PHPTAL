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
require_once 'PHPTAL/PhpTransformer.php';

class PhpTransformerTest extends PHPUnit2_Framework_TestCase
{
    function testBooleanOperators()
    {
        $this->assertEquals('! $a', PHPTAL_PhpTransformer::transform('not a'));
        $this->assertEquals('$a || $b', PHPTAL_PhpTransformer::transform('a or b'));
        $this->assertEquals('($a || $b) && ($z && $x) && (10 < 100)', PHPTAL_PhpTransformer::transform('(a or b) and (z and x) and (10 < 100)'));
    }
    
    function testPathes()
    {
        $this->assertEquals('$a', PHPTAL_PhpTransformer::transform('a'));
        $this->assertEquals('$a->b', PHPTAL_PhpTransformer::transform('a.b'));
        $this->assertEquals('$a->b->c', PHPTAL_PhpTransformer::transform('a.b.c'));
    }
    
    function testFunctionAndMethod()
    {
        $this->assertEquals('a()', PHPTAL_PhpTransformer::transform('a()'));
        $this->assertEquals('$a->b()', PHPTAL_PhpTransformer::transform('a.b()'));
        $this->assertEquals('$a->b[$c]()', PHPTAL_PhpTransformer::transform('a.b[c]()'));
        $this->assertEquals('$a->b[$c]->d()', PHPTAL_PhpTransformer::transform('a.b[c].d()'));
    }

    function testArrays()
    {
        $this->assertEquals('$a[0]', PHPTAL_PhpTransformer::transform('a[0]'));
        $this->assertEquals('$a["my key"]', PHPTAL_PhpTransformer::transform('a["my key"]'));
        $this->assertEquals('$a->b[$c]', PHPTAL_PhpTransformer::transform('a.b[c]'));
    }

    function testConcat()
    {
        $this->assertEquals('$a . $c . $b', PHPTAL_PhpTransformer::transform('a . c . b'));
    }

    function testStrings()
    {
        $this->assertEquals('"prout"', PHPTAL_PhpTransformer::transform('"prout"'));
        $this->assertEquals("'prout'", PHPTAL_PhpTransformer::transform("'prout'"));
        $this->assertEquals('"my string\" still in string"', 
                            PHPTAL_PhpTransformer::transform('"my string\" still in string"'));
        $this->assertEquals("'my string\' still in string'", 
                            PHPTAL_PhpTransformer::transform("'my string\' still in string'"));
    }

    function testStringParams()
    {
        $this->assertEquals('strtolower(\'AAA\')', 
                            PHPTAL_PhpTransformer::transform('strtolower(\'AAA\')')
                           );
    }

    function testEvals()
    {
        $this->assertEquals('$$a', PHPTAL_PhpTransformer::transform('$a'));
        $this->assertEquals('$a->{$b}->c', PHPTAL_PhpTransformer::transform('a.$b.c'));
        $this->assertEquals('$a->{$x->y}->z', PHPTAL_PhpTransformer::transform('a.{x.y}.z'));
        $this->assertEquals('$a->{$x->y}()', PHPTAL_PhpTransformer::transform('a.{x.y}()'));
    }

    function testOperators()
    {
        $this->assertEquals('$a + 100 / $b == $d', PHPTAL_PhpTransformer::transform('a + 100 / b == d'));
        $this->assertEquals('$a * 10.03', PHPTAL_PhpTransformer::transform('a * 10.03'));
    }

    function testStatics()
    {
        $this->assertEquals('MyClass::method()', PHPTAL_PhpTransformer::transform('MyClass::method()'));
        $this->assertEquals('MyClass::CONSTANT', PHPTAL_PhpTransformer::transform('MyClass::CONSTANT'));
        $this->assertEquals('MyClass::CONSTANT_UNDER', PHPTAL_PhpTransformer::transform('MyClass::CONSTANT_UNDER'));
        $this->assertEquals('MyClass::CONSTANT_UNDER6', PHPTAL_PhpTransformer::transform('MyClass::CONSTANT_UNDER6'));
        $this->assertEquals('MyClass::ConsTant', PHPTAL_PhpTransformer::transform('MyClass::ConsTant'));
        $this->assertEquals('MyClass::$static', PHPTAL_PhpTransformer::transform('MyClass::$static'));
        $this->assertEquals('MyClass::$static->foo()', PHPTAL_PhpTransformer::transform('MyClass::$static.foo()'));
    }

    function testStringEval()
    {
        $this->assertEquals('"xxx {$a->{$b}->c[$x]} xxx"', PHPTAL_PhpTransformer::transform('"xxx ${a.$b.c[x]} xxx"'));
    }

    function testDefines()
    {
        $this->assertEquals('MY_DEFINE . $a->b', PHPTAL_PhpTransformer::transform('@MY_DEFINE . a.b'));
    }

    function testPrefix()
    {
        $this->assertEquals('$C->a->b->c[$C->x]', PHPTAL_PhpTransformer::transform('a.b.c[x]', '$C->'));
        $this->assertEquals('$C->a->{$C->b}->c[$C->x]', PHPTAL_PhpTransformer::transform('a.$b.c[x]', '$C->'));
        $this->assertEquals('"xxx {$C->a->{$C->b}->c[$C->x]} xxx"', PHPTAL_PhpTransformer::transform('"xxx ${a.$b.c[x]} xxx"', '$C->'));
    }

    function testKeywords()
    {
        $this->assertEquals('true != false', PHPTAL_PhpTransformer::transform('true ne false'));
        $this->assertEquals('$test == null', PHPTAL_PhpTransformer::transform('test eq null'));
    }

    function testTernaryOperator()
    {
        $this->assertEquals('($test)?true:false', PHPTAL_PhpTransformer::transform('(test)?true:false'));
    }

    function testInstanceOf()
    {
        $this->assertEquals('$test instanceOf Foo', PHPTAL_PhpTransformer::transform('test instanceOf Foo'));
    }
}

?>
