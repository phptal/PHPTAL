<?php

require_once 'config.php';
require_once 'PHPTAL/Tales.php';

class TalReplaceTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-replace.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.01.html');
        $this->assertEquals($exp, $res);
    }

    function testVar()
    {
        $tpl = new PHPTAL('input/tal-replace.02.html');
        $tpl->replace = 'my replace';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $tpl = new PHPTAL('input/tal-replace.03.html');
        $tpl->replace = '<foo><bar/></foo>';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.03.html');
        $this->assertEquals($exp, $res);
    }

    function testNothing()
    {
        $tpl = new PHPTAL('input/tal-replace.04.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.04.html');
        $this->assertEquals($exp, $res);
    }

    function testDefault()
    {
        $tpl = new PHPTAL('input/tal-replace.05.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.05.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = new PHPTAL('input/tal-replace.06.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.06.html');
        $this->assertEquals($exp, $res);
    }
}

?>
