<?php

require_once 'config.php';
require_once 'PHPTAL.php';
require_once 'PHPTAL/Parser.php';
require_once 'PHPTAL/CodeGenerator.php';
require_once 'PHPTAL/Attribute/TAL/Comment.php';

if (!class_exists('DummyTag')) {
    class DummyTag {}
}

class TalConditionTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-condition.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-condition.01.html');
        $this->assertEquals($exp, $res);
    }

    function testNot()
    {
        $tpl = new PHPTAL('input/tal-condition.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-condition.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testExists()
    {
        $tpl = new PHPTAL('input/tal-condition.03.html');
        $tpl->somevar = true;
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-condition.03.html');
        $this->assertEquals($exp, $res);        
    }
}
        
?>
