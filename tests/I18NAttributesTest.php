<?php

require_once 'config.php';
require_once 'PHPTAL.php';
require_once 'I18NDummyTranslator.php';

class I18NAttributesTest extends PHPUnit2_Framework_TestCase
{
    function testSingle()
    {
        $t = new DummyTranslator();
        $t->setTranslation('my-title', 'mon titre');
        
        $tpl = new PHPTAL('input/i18n-attributes-01.html');
        $tpl->setTranslator($t);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/i18n-attributes-01.html');
        // $this->assertEquals($exp, $res);
    }

    function testMultiple()
    {
    }

    function testDefault()
    {
    }

    function testReplacedByTalAttributes()
    {
    }

    function testUnableToTranslate()
    {
    }
}

?>
