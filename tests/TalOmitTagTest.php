<?php

require_once 'config.php';
require_once 'PHPTAL.php';


class TalOmitTagTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-omit-tag.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-omit-tag.01.html');
        $this->assertEquals($exp, $res);
    }
}
        
?>
