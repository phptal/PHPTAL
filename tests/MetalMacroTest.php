<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class MetalMacroTest extends PHPUnit2_Framework_TestCase
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/metal-macro.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-macro.01.html');
        $this->assertEquals($exp, $res);
    }

    function testExternalMacro()
    {
        $tpl = new PHPTAL('input/metal-macro.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-macro.02.html');
        $this->assertEquals($exp, $res);
    }
}

?>
