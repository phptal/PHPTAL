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
 * @version  SVN: $Id: PHPTAL.php 517 2009-04-07 10:56:30Z kornel $
 * @link     http://phptal.motion-twin.com/ 
 */
 
class PHPTAL_CodeCacheTest extends PHPTAL
{
    public $testHasParsed = false;
    function parse()
    {
        $this->testHasParsed = true;
        return parent::parse();
    }
}

class CodeCacheTest extends PHPTAL_TestCase
{
    private $phptal;
    private $codeDestination;

    private function resetPHPTAL()
    {
        $this->phptal = new PHPTAL_CodeCacheTest();
        $this->phptal->setForceReparse(false);
        $this->assertFalse($this->phptal->getForceReparse());

        $this->phptal->setPhpCodeDestination(dirname(__FILE__).DIRECTORY_SEPARATOR.'temp_output');
        $this->codeDestination = $this->phptal->getPhpCodeDestination();
    }

    private function clearCache()
    {
        $this->assertContains(dirname(__FILE__),$this->codeDestination);
        foreach (glob($this->codeDestination.'tpl_*') as $tpl) {
            $this->assertTrue(unlink($tpl), "Delete $tpl");
        }
    }

    function setUp()
    {
        parent::setUp();
        $this->resetPHPTAL();
        $this->clearCache();
    }

    function tearDown()
    {
        $this->clearCache();
    }

    function testNoParseOnReexecution()
    {
        $this->phptal->setSource('<p>hello</p>');
        $this->phptal->execute();

        $this->assertTrue($this->phptal->testHasParsed, "Initial parse");

        $this->phptal->testHasParsed = false;
        $this->phptal->execute();

        $this->assertFalse($this->phptal->testHasParsed, "No reparse");
    }

    function testNoParseOnReset()
    {
        $this->phptal->setSource('<p>hello2</p>');
        $this->phptal->execute();

        $this->assertTrue($this->phptal->testHasParsed, "Initial parse");
        
        $this->resetPHPTAL();

        $this->phptal->setSource('<p>hello2</p>');
        $this->phptal->execute();

        $this->assertFalse($this->phptal->testHasParsed, "No reparse");
    }

    function testReparseAfterTouch()
    {
        $time1 = filemtime('input/code-cache-01.html');
        touch('input/code-cache-01.html', time());
        clearstatcache();
        $time2 = filemtime('input/code-cache-01.html');
        $this->assertNotEquals($time1,$time2,"touch() must work");


        $this->phptal->setTemplate('input/code-cache-01.html');
        $this->phptal->execute();
        $this->assertTrue($this->phptal->testHasParsed, "Initial parse");

        $this->resetPHPTAL();

        touch('input/code-cache-01.html', $time1);
        clearstatcache();

        $this->phptal->setTemplate('input/code-cache-01.html');
        $this->phptal->execute();

        $this->assertTrue($this->phptal->testHasParsed, "Reparse");
    }

/*
    function testGarbageRemoval()
    {
        $this->markTestIncomplete("cache doesnt use timestamps anymore");
        
        $this->phptal->setTemplate('input/code-cache-01.html');
        $this->phptal->execute();

        foreach (glob($this->codeDestination.'*') as $file) {
            touch($file, time() - 3600*24*100);
        }

        $this->phptal->cleanUpGarbage(); // should delete all files

        $this->phptal->testHasParsed = false;
        $this->phptal->setTemplate('input/code-cache-01.html');
        $this->phptal->execute();

        $this->assertTrue($this->phptal->testHasParsed, "Reparse");
    }
*/    
}
