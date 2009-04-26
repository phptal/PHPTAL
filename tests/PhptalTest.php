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

require_once dirname(__FILE__)."/config.php";

class PhptalTest extends PHPTAL_TestCase
{
    function test01()
    {
        $tpl = $this->newPHPTAL('input/phptal.01.html');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testXmlHeader()
    {
        $tpl = $this->newPHPTAL('input/phptal.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/phptal.02.html');
        $this->assertEquals($exp, $res);
    }

    function testExceptionNoEcho()
    {
        $tpl = $this->newPHPTAL('input/phptal.03.html');
        ob_start();
        try {
            $res = $tpl->execute();
        }
        catch (Exception $e){
        }
        $c = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('', $c);
    }

    function testRepositorySingle()
    {
        $tpl = $this->newPHPTAL('phptal.01.html');
        $tpl->setTemplateRepository('input');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testRepositorySingleWithSlash()
    {
        $tpl = $this->newPHPTAL('phptal.01.html');
        $tpl->setTemplateRepository('input/');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testRepositoryMuliple()
    {
        $tpl = $this->newPHPTAL('phptal.01.html');
        $tpl->setTemplateRepository(array('bar', 'input/'));
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testSetTemplate()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTemplateRepository(array('bar', 'input/'));
        $tpl->setOutputMode(PHPTAL::XML);
        $tpl->setTemplate('phptal.01.html');
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testXmlMode()
    {
        $tpl = $this->newPHPTAL('input/xml.04.xml');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = trim_string($tpl->execute());
        $exp = trim_file('input/xml.04.xml');
        $this->assertEquals($exp, $res);
    }

    function testSource()
    {
        $source = '<span tal:content="foo"/>';
        $tpl = $this->newPHPTAL();
        $tpl->foo = 'foo value';
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->assertEquals('<span>foo value</span>', $res);

        $this->assertRegExp('/^tpl_\d{8}_/', $tpl->getFunctionName());
        $this->assertContains('string', $tpl->getFunctionName());
        $this->assertNotContains(PHPTAL_VERSION, $tpl->getFunctionName());
    }

    /**
     * @todo: write it :)
     */
    function testFunctionNameChangesWhenSettingsChange()
    {
        $this->markTestIncomplete();
    }

    function testSourceWithPath()
    {
        $source = '<span tal:content="foo"/>';
        $tpl = $this->newPHPTAL();
        $tpl->foo = 'foo value';
        $tpl->setSource($source, $fakename = 'abc12345');
        $res = $tpl->execute();
        $this->assertEquals('<span>foo value</span>', $res);
        $this->assertRegExp('/^tpl_\d{8}_/', $tpl->getFunctionName());
        $this->assertContains($fakename, $tpl->getFunctionName());
        $this->assertNotContains(PHPTAL_VERSION, $tpl->getFunctionName());
    }

    function testStripComments()
    {
        $tpl = $this->newPHPTAL('input/phptal.04.html');
        $exp = trim_file('output/phptal.04.html');
        $tpl->stripComments(true);
        $res = $tpl->execute();
        $res = trim_string($res);
        $this->assertEquals($exp, $res);
    }

    function testUnknownOutputMode()
    {
        try {
            $tpl = $this->newPHPTAL();
            $tpl->setOutputMode('unknown');
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e){
            $this->assertTrue(true);
        }
    }

    function testZeroedContent()
    {
        $tpl = $this->newPHPTAL('input/phptal.05.html');
        $res = $tpl->execute();
        $exp = trim_file('input/phptal.05.html');
        $this->assertEquals($exp, $res);
    }

    function testOnlineExpression()
    {
        $tpl = $this->newPHPTAL('input/phptal.06.html');
        $tpl->foo = '<p>hello</p>';
        $res = $tpl->execute();
        $exp = trim_file('output/phptal.06.html');
        $this->assertEquals($exp,$res);
    }
}

?>
