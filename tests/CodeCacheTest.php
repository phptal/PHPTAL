<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */


class PHPTAL_TestCodeCache extends PHPTAL
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
        $this->phptal = new PHPTAL_TestCodeCache();
        $this->phptal->setForceReparse(false);
        $this->assertFalse($this->phptal->getForceReparse());

        if (function_exists('sys_get_temp_dir')) {
            $tmpdirpath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'temp_output';
            if (!is_dir($tmpdirpath)) mkdir($tmpdirpath);
        }
        else $this->markTestSkipped("Newer PHP needed");

        $this->assertTrue(is_dir($tmpdirpath));
        $this->assertTrue(is_writable($tmpdirpath));

        $this->phptal->setPhpCodeDestination($tmpdirpath);
        $this->codeDestination = $this->phptal->getPhpCodeDestination();
    }

    private function clearCache()
    {
        $this->assertContains(DIRECTORY_SEPARATOR.'temp_output'.DIRECTORY_SEPARATOR, $this->codeDestination);
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
        parent::tearDown();
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
        if (!is_writable('input/code-cache-01.html')) $this->markTestSkipped();

        $time1 = filemtime('input/code-cache-01.html');
        touch('input/code-cache-01.html', time());
        clearstatcache();
        $time2 = filemtime('input/code-cache-01.html');
        $this->assertNotEquals($time1, $time2, "touch() must work");


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

    function testGarbageRemoval()
    {
        $src = '<test uniq="'.time().mt_rand().'" phptal:cache="1d" />';
        $this->phptal->setSource($src);
        $this->phptal->execute();

        $this->assertTrue($this->phptal->testHasParsed, "Parse");

        $this->phptal->testHasParsed = false;
        $this->phptal->setSource($src);
        $this->phptal->execute();

        $this->assertFalse($this->phptal->testHasParsed, "Reparse!?");

        $files = glob($this->codeDestination.'*');
        $this->assertEquals(2, count($files)); // one for template, one for cache
        foreach ($files as $file) {
            $this->assertFileExists($file);
            touch($file, time() - 3600*24*100);
        }
        clearstatcache();

        $this->phptal->cleanUpGarbage(); // should delete all files

        clearstatcache();

        // can't check for reparse, because PHPTAL uses function_exists() as a shortcut!
        foreach ($files as $file) {
            $this->assertFileNotExists($file);
        }
    }

    function testNested()
    {
        $this->phptal->setSource('<div phptal:cache="1m per string: 1"> 1 <div phptal:cache="1h per string: 2"> 2 </div> </div>');

        $this->assertEquals(normalize_html('<div> 1 <div> 2 </div> </div>'), normalize_html($this->phptal->execute()), "1st run");
        $this->assertEquals(normalize_html('<div> 1 <div> 2 </div> </div>'), normalize_html($this->phptal->execute()), "2nd run");
        $this->assertEquals(normalize_html('<div> 1 <div> 2 </div> </div>'), normalize_html($this->phptal->execute()), "3rd run");
        $this->assertEquals(normalize_html('<div> 1 <div> 2 </div> </div>'), normalize_html($this->phptal->execute()), "4th run");
    }
}
