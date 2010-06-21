<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */


class ReadableErrorTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $this->assertThrowsInLine(2, 'input/error-01.html');
    }

    function testMacro()
    {
        try {
            $tpl = $this->newPHPTAL('input'.DIRECTORY_SEPARATOR.'error-02.html');
            $res = $tpl->execute();
            $this->fail("Not thrown");
        }
        catch (PHPTAL_Exception $e)
        {
            $expected = 'input'.DIRECTORY_SEPARATOR.'error-02.macro.html';
            $this->assertType('string',$e->srcFile);
            $this->assertContains($expected, $e->srcFile);
            $this->assertEquals(2, $e->srcLine);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

    function testAfterMacro()
    {
        $this->assertThrowsInLine(3, 'input'.DIRECTORY_SEPARATOR.'error-03.html');
    }

    function testParseError()
    {
        $this->assertThrowsInLine(7, 'input'.DIRECTORY_SEPARATOR.'error-04.html');
    }

    function testMissingVar()
    {
        $this->assertThrowsInLine(5, 'input'.DIRECTORY_SEPARATOR.'error-05.html');
    }

    function testMissingVarInterpol()
    {
        $this->markTestSkipped("can't fix it now");
        $this->assertThrowsInLine(5, 'input'.DIRECTORY_SEPARATOR.'error-06.html');
    }

    function testMissingExpr()
    {
        $this->assertThrowsInLine(6, 'input'.DIRECTORY_SEPARATOR.'error-07.html');
    }

    function testPHPSyntax()
    {
        $this->assertThrowsInLine(9, 'input'.DIRECTORY_SEPARATOR.'error-08.html');
    }

    function testTranslate()
    {
        $this->assertThrowsInLine(8, 'input'.DIRECTORY_SEPARATOR.'error-09.html');
    }

    function testMacroName()
    {
        $this->assertThrowsInLine(4, 'input'.DIRECTORY_SEPARATOR.'error-10.html');
    }

    function testTALESParse()
    {
        $this->assertThrowsInLine(2, 'input'.DIRECTORY_SEPARATOR.'error-11.html');
    }

    function testMacroNotExists()
    {
        $this->assertThrowsInLine(3, 'input'.DIRECTORY_SEPARATOR.'error-12.html');
    }

    function testLocalMacroNotExists()
    {
        $this->assertThrowsInLine(5, 'input'.DIRECTORY_SEPARATOR.'error-12.html');
    }

    function assertThrowsInLine($line, $file)
    {
        try {
            $tpl = $this->newPHPTAL($file);
            $tpl->a_number = 1;
            $res = $tpl->execute();
            $this->fail("Not thrown");
        }
        catch (PHPTAL_TemplateException $e)
        {
            $msg = $e->getMessage();
            $this->assertType('string',$e->srcFile, $msg);
            $this->assertContains($file, $e->srcFile, $msg);
            $this->assertEquals($line, $e->srcLine, $msg);
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }
}


