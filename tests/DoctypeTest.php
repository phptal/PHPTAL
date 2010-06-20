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


class DoctypeTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/doctype.01.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/doctype.01.html');
        $this->assertEquals($exp, $res);
    }

    function testPreservesNewlineAfterDoctype()
    {
        $src = "<!DOCTYPE html>\n\n\n<html></html>";
        $tpl = $this->newPHPTAL()->setSource($src);
        $this->assertEquals($src,$tpl->execute());

        $src = "<!DOCTYPE html>\n<html></html>";
        $tpl->setSource($src);
        $this->assertEquals($src,$tpl->execute());
    }

    function testMacro()
    {
        $tpl = $this->newPHPTAL('input/doctype.02.user.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/doctype.02.html');
        $this->assertEquals($exp, $res);
    }

    function testDeepMacro()
    {
        $tpl = $this->newPHPTAL('input/doctype.03.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/doctype.03.html');
        $this->assertEquals($exp, $res);
    }

    function testDtdInline()
    {
        $tpl = $this->newPHPTAL('input/doctype.04.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/doctype.04.html');
        $this->assertEquals($exp, $res);
    }

    function testClearedOnReexecution()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><whatever/>');

        $this->assertContains("DOCTYPE html PUBLIC", $tpl->execute());
        $this->assertContains("DOCTYPE html PUBLIC", $tpl->execute());

        $tpl->setSource('<whatever/>');

        $this->assertNotContains("DOCTYPE html PUBLIC", $tpl->execute());
        $this->assertNotContains("DOCTYPE html PUBLIC", $tpl->execute());
    }

    /**
     * this is pretty crazy case of PHPTAL being reused while template is still being executed
     */
    function testClearedOnNestedReexecution()
    {
        $tpl = $this->newPHPTAL();
        $tpl->tpl = $tpl;

        $tpl->setSource('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <hack tal:define="hack php:tpl.setSource(&quot;&lt;hacked/&gt;&quot;)" tal:content="structure hack/execute"/>');

        $this->assertEquals(normalize_html('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><hack><hacked></hacked></hack>'),
                            normalize_html($tpl->execute()));
    }
}
