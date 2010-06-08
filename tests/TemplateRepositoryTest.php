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
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */


class TemplateRepositoryTest extends PHPTAL_TestCase
{
    function testLooksInRepo()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTemplateRepository(dirname(__FILE__).'/input');
        $tpl->setTemplate('phptal.01.html');
        $tpl->execute();
    }

    function testSkipsNotFound()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTemplateRepository(dirname(__FILE__).'/invalid');
        $tpl->setTemplateRepository(dirname(__FILE__).'/input');
        $tpl->setTemplateRepository(dirname(__FILE__).'/bogus');
        $tpl->setTemplate('phptal.02.html');
        $tpl->execute();
    }

    /**
     * @expectedException PHPTAL_IOException
     */
    function testFailsIfNoneMatch()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTemplateRepository(dirname(__FILE__).'/invalid');
        $tpl->setTemplateRepository(dirname(__FILE__).'/error');
        $tpl->setTemplateRepository(dirname(__FILE__).'/bogus');
        $tpl->setTemplate('phptal.01.html');
        $tpl->execute();
    }

    function testRepositoriesAreStrings()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTemplateRepository('/footest');
        $tpl->setTemplateRepository('bartest');
        $tpl->setTemplateRepository('testbaz/');

        $repos = $tpl->getTemplateRepositories();
        $this->assertType('array', $repos);
        $this->assertEquals(3, count($repos));

        foreach($repos as $repo)
        {
            $this->assertType('string', $repo);
            $this->assertContains('test', $repo);
        }
    }

    function testRepositoryClear()
    {
        $tpl = $this->newPHPTAL();
        $this->assertEquals(0, count($tpl->getTemplateRepositories()));

        $tpl->setTemplateRepository(array('foo', 'bar'));
        $this->assertEquals(2, count($tpl->getTemplateRepositories()));

        $tpl->clearTemplateRepositories();
        $this->assertEquals(0, count($tpl->getTemplateRepositories()));
    }
}
