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
 * @link     http://phptal.motion-twin.com/
 */

class ReadableErrorTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/error-01.html');
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
            $tpl = $this->newPHPTAL('input/error-02.html');
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
            $tpl = $this->newPHPTAL('input/error-03.html');
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
