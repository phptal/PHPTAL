<?php

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
}

?>
