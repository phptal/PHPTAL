<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class PhptalTest extends PHPUnit2_Framework_TestCase 
{
    function test01()
    {
        $tpl = new PHPTAL('input/phptal.01.html');
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testXmlHeader()
    {
        $tpl = new PHPTAL('input/phptal.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/phptal.02.html');
        $this->assertEquals($exp, $res);
    }
}
        
?>
