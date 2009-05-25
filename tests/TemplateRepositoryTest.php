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

require_once dirname(__FILE__)."/config.php";

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
}
