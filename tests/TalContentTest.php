<?php

require_once 'config.php';
require_once 'PHPTAL/Tales.php';

class TalContentTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-content.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.01.html');
        $this->assertEquals($exp, $res);
    }

    function testVar()
    {
        $tpl = new PHPTAL('input/tal-content.02.html');
        $tpl->content = 'my content';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $tpl = new PHPTAL('input/tal-content.03.html');
        $tpl->content = '<foo><bar/></foo>';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.03.html');
        $this->assertEquals($exp, $res);
    }

    function testNothing()
    {
        $tpl = new PHPTAL('input/tal-content.04.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.04.html');
        $this->assertEquals($exp, $res);
    }
    
    function testDefault()
    {
        $tpl = new PHPTAL('input/tal-content.05.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.05.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = new PHPTAL('input/tal-content.06.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.06.html');
        $this->assertEquals($exp, $res);
    }
}

?>
