<?php

require_once 'config.php';
require_once 'PHPTAL/Tales.php';

class TalesStringTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $this->assertEquals('\'this is a string\'', phptal_tales_string('this is a string'));
    }

    function testDoubleDollar()
    {
        $this->assertEquals('\'this is a $string\'', phptal_tales_string('this is a $$string'));
    }

    function testSubPathSimple()
    {
        $res = phptal_tales_string('hello $name how are you ?');
        $rgm = preg_match('/\'hello \'\s*?\.\s*?php.+\'name\'.+\.\s*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPath()
    {
        $res = phptal_tales_string('${name}');
        $rgm = preg_match('/\'\'\s*?\.\s*?php.+\'name\'.+\.\s*?\'\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPathExtended()
    {
        $res = phptal_tales_string('hello ${user/name} how are you ?');
        $rgm = preg_match('/\'hello \'\s*?\.\s*?php.+\'user\/name\'.+\.\s*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }
}

?>
