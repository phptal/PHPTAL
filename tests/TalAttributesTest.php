<?php

require_once 'config.php';
require_once 'PHPTAL.php';

if (!class_exists('DummyTag')) {
    class DummyTag {}
}

class TalAttributesTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-attributes.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.01.html');
        $this->assertEquals($exp, $res);
    }

    function testWithContent()
    {
        $tpl = new PHPTAL('input/tal-attributes.02.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.02.html');
        $this->assertEquals($exp, $res);
    }

    function testMultiples()
    {
        $tpl = new PHPTAL('input/tal-attributes.03.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.03.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = new PHPTAL('input/tal-attributes.04.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.04.html');
        $this->assertEquals($exp, $res);
    }

    function testMultipleChains()
    {
        $tpl = new PHPTAL('input/tal-attributes.05.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.05.html');
        $this->assertEquals($exp, $res);
    }

    //TODO: test and implement xhtml boolean attributes
}
        
?>
