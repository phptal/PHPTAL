<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class MetalSlotTest extends PHPUnit2_Framework_TestCase
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/metal-slot.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.01.html');
        $this->assertEquals($exp, $res);
    }

    function testRecusiveFill()
    {
        $tpl = new PHPTAL('input/metal-slot.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.02.html');
        $this->assertEquals($exp, $res);
    }
}

?>
