<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class I18NNameTest extends PHPUnit2_Framework_TestCase
{
    function testSet()
    {
        $tpl = new PHPTAL('input/i18n-name-01.html');
        $tpl->setTranslator( new DummyTranslator() );
        $res = $tpl->execute();
        $this->assertEquals(true, array_key_exists('test', $tpl->getTranslator()->vars));
        $this->assertEquals('test value', $tpl->getTranslator()->vars['test']);
    }

    function testInterpolation()
    {
        $tpl = new PHPTAL('input/i18n-name-02.html');
        $tpl->setTranslator( new DummyTranslator() );
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/i18n-name-02.html');
        $this->assertEquals($exp, $res);
    }

    function testMultipleInterpolation()
    {
        $tpl = new PHPTAL('input/i18n-name-03.html');
        $tpl->setTranslator( new DummyTranslator() );
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/i18n-name-03.html');
        $this->assertEquals($exp, $res);
    }
}

