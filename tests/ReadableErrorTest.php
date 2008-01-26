<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class ReadableErrorTest extends PHPUnit_Framework_TestCase
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
            $this->assertTrue(strpos($e->srcFile, 'input/error-01.html') !== false);
            $this->assertEquals(2, $e->srcLine);
        }
        catch (Exception $e){
            throw $e;
        }
    }

    function testMacro()
    {
        $expected = 'input' . DIRECTORY_SEPARATOR . 'error-02.macro.html';
        
        try {
            $tpl = new PHPTAL('input/error-02.html');
            $res = $tpl->execute();
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e){
            $this->assertTrue(strpos($e->srcFile, $expected) !== false);
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
            $this->assertTrue(strpos($e->srcFile, 'input/error-03.html') !== false);
            $this->assertEquals(3, $e->srcLine);
        }
        catch (Exception $e){
            throw $e;
        }
    }
}

?>
