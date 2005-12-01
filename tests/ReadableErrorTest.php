<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class ReadableErrorTest extends PHPUnit2_Framework_TestCase
{
    function testSimple()
    { 
        $tpl = new PHPTAL('input/error-01.html');
        try {
            $tpl->prepare();
            $res = $tpl->execute();
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e){
            $this->assertEquals('input/error-01.html', $e->srcFile);
            $this->assertEquals(2, $e->srcLine);
        }
        catch (Exception $e){
            throw $e;
        }
    }

    function testMacro()
    {
        $expected = PHPTAL_OS_WIN 
          ? 'input\\error-02.macro.html' 
          : 'input/error-02.macro.html';
        
        try {
            $tpl = new PHPTAL('input/error-02.html');
            $res = $tpl->execute();
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e){
            $this->assertTrue(strpos($expected, $e->srcFile) !== false);
            $this->assertEquals(2, $e->srcLine);
        }
        catch (Exception $e){
            throw $e;
        }
    }
    
    function testAfterMacro()
    {
        try {
            $tpl = new PHPTAL('input/error-03.html');
            $res = $tpl->execute();
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e){
            $this->assertTrue(strpos('input/error-03.html', $e->srcFile) !== false);
            $this->assertEquals(3, $e->srcLine);
        }
        catch (Exception $e){
            throw $e;
        }
    }
}

?>
