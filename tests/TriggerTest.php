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



class StupidCacheTrigger implements PHPTAL_Trigger
{
    public $isCaching = false;
    public $cachePath = '';

    public function start($phptalId, $tpl)
    {
        $this->cachePath = 'trigger.' . $tpl->getContext()->someId;

        // if already cached, read the cache and tell PHPTAL to
        // ignore the tag content
        if (file_exists($this->cachePath)) {
            $this->isCaching = false;
            readfile($this->cachePath);
            return self::SKIPTAG;
        }

        // no cache, we start and output buffer and tell
        // PHPTAL to proceed (ie: execute the tag content)
        $this->isCaching = true;
        ob_start();
        return self::PROCEED;
    }

    public function end($phptalId, $tpl)
    {
        // end of tag, if cached file used, do nothing
        if (!$this->isCaching)
            return;

        // otherwise, get the content of the output buffer
        // and write it into the cache file for later usage
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;

        $f = fopen($this->cachePath, 'w');
        fwrite($f, $content);
        fclose($f);
    }
}

class TriggerTest extends PHPTAL_TestCase
{
    public function setUp()
    {
        parent::setUp();

        if (!is_writable('.')) $this->markTestSkipped();

        if (file_exists('trigger.10')) unlink('trigger.10');
        if (file_exists('trigger.11')) unlink('trigger.11');
    }

    public function tearDown()
    {
        if (file_exists('trigger.10')) unlink('trigger.10');
        if (file_exists('trigger.11')) unlink('trigger.11');

        parent::tearDown();
    }

    public function testSimple()
    {
        $trigger = new StupidCacheTrigger();
        $tpl = $this->newPHPTAL('input/trigger.01.html');
        $tpl->addTrigger('someid', $trigger);
        $exp = normalize_html_file('output/trigger.01.html');

        $tpl->someId = 10;
        $res = normalize_html($tpl->execute());
        $this->assertEquals($exp, $res);
        $this->assertTrue($trigger->isCaching);
        $this->assertEquals('trigger.10', $trigger->cachePath);

        $tpl->someId = 10;
        $res = normalize_html($tpl->execute());
        $this->assertEquals($exp, $res);
        $this->assertFalse($trigger->isCaching);
        $this->assertEquals('trigger.10', $trigger->cachePath);
    }
}

