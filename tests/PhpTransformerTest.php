<?php

require_once 'config.php';
require_once 'PHPTAL/PhpTransformer.php';

class PhpTransformerTest extends PHPUnit2_Framework_TestCase
{
    private $t;

    function setUp()
    {
        $this->t = new PHPTAL_PhpTransformer();
    }

    function testBooleanOperators()
    {
        $this->assertEquals('! $a', $this->t->transform('not a'));
        $this->assertEquals('$a || $b', $this->t->transform('a or b'));
        $this->assertEquals('($a || $b) && ($z && $x) && (10 < 100)', $this->t->transform('(a or b) and (z and x) and (10 < 100)'));
    }
    
    function testPathes()
    {
        $this->assertEquals('$a', $this->t->transform('a'));
        $this->assertEquals('$a->b', $this->t->transform('a.b'));
        $this->assertEquals('$a->b->c', $this->t->transform('a.b.c'));
    }
    
    function testFunctionAndMethod()
    {
        $this->assertEquals('a()', $this->t->transform('a()'));
        $this->assertEquals('$a->b()', $this->t->transform('a.b()'));
        $this->assertEquals('$a->b[$c]()', $this->t->transform('a.b[c]()'));
        $this->assertEquals('$a->b[$c]->d()', $this->t->transform('a.b[c].d()'));
    }

    function testArrays()
    {
        $this->assertEquals('$a[0]', $this->t->transform('a[0]'));
        $this->assertEquals('$a["my key"]', $this->t->transform('a["my key"]'));
        $this->assertEquals('$a->b[$c]', $this->t->transform('a.b[c]'));
    }

    function testConcat()
    {
        $this->assertEquals('$a . $c . $b', $this->t->transform('a . c . b'));
    }

    function testStrings()
    {
        $this->assertEquals('"prout"', $this->t->transform('"prout"'));
        $this->assertEquals("'prout'", $this->t->transform("'prout'"));
        $this->assertEquals('"my string\" still in string"', 
                            $this->t->transform('"my string\" still in string"'));
        $this->assertEquals("'my string\' still in string'", 
                            $this->t->transform("'my string\' still in string'"));
    }

    function testEvals()
    {
        $this->assertEquals('$$a', $this->t->transform('$a'));
        $this->assertEquals('$a->{$b}->c', $this->t->transform('a.$b.c'));
        $this->assertEquals('$a->{$x->y}->z', $this->t->transform('a.{x.y}.z'));
        $this->assertEquals('$a->{$x->y}()', $this->t->transform('a.{x.y}()'));
    }

    function testOperators()
    {
        $this->assertEquals('$a + 100 / $b == $d', $this->t->transform('a + 100 / b == d'));
        $this->assertEquals('$a * 10.03', $this->t->transform('a * 10.03'));
    }

    function testStatics()
    {
        $this->assertEquals('MyClass::method()', $this->t->transform('MyClass::method()'));
        $this->assertEquals('MyClass::CONSTANT', $this->t->transform('MyClass::CONSTANT'));
        $this->assertEquals('MyClass::$static', $this->t->transform('MyClass::$static'));
        $this->assertEquals('MyClass::$static->foo()', $this->t->transform('MyClass::$static.foo()'));
    }

    function testStringEval()
    {
        $this->assertEquals('"xxx {$a->{$b}->c[$x]} xxx"', $this->t->transform('"xxx ${a.$b.c[x]} xxx"'));
    }

    function testDefines()
    {
        $this->assertEquals('MY_DEFINE . $a->b', $this->t->transform('@MY_DEFINE . a.b'));
    }

    function testPrefix()
    {
        $this->t->prefix = '$C->';
        $this->assertEquals('$C->a->b->c[$C->x]', $this->t->transform('a.b.c[x]'));
        $this->assertEquals('$C->a->{$C->b}->c[$C->x]', $this->t->transform('a.$b.c[x]'));
        $this->assertEquals('"xxx {$C->a->{$C->b}->c[$C->x]} xxx"', $this->t->transform('"xxx ${a.$b.c[x]} xxx"'));
    }

    function testTrueFalseKeywords()
    {
        $this->assertEquals('true != false', $this->t->transform('true ne false'));
    }
}

?>
