<?php

require_once 'config.php';
require_once 'PHPTAL.php';

if (!class_exists('OnErrorDummyObject')) {
    class OnErrorDummyObject 
    {
        function throwException()
        {
            throw new Exception('error thrown');
        }
    }
}

class TalOnErrorTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-on-error.01.html');
        $tpl->dummy = new OnErrorDummyObject();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-on-error.01.html');
        $this->assertEquals($exp, $res);
        $this->assertEquals(1, count($tpl->errors));
        $this->assertEquals('error thrown', $tpl->errors[0]->getMessage());
    }

    function testEmpty()
    {
        $tpl = new PHPTAL('input/tal-on-error.02.html');
        $tpl->dummy = new OnErrorDummyObject();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-on-error.02.html');
        $this->assertEquals(1, count($tpl->errors));
        $this->assertEquals('error thrown', $tpl->errors[0]->getMessage());
        $this->assertEquals($exp, $res);
    }

    function testReplaceStructure()
    {
        $tpl = new PHPTAL('input/tal-on-error.03.html');
        $tpl->dummy = new OnErrorDummyObject();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-on-error.03.html');
        $this->assertEquals(1, count($tpl->errors));
        $this->assertEquals('error thrown', $tpl->errors[0]->getMessage());
        $this->assertEquals($exp, $res);        
    }
}
        
?>
