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
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */

class MyTestResolver implements PHPTAL_SourceResolver
{
    public $called=0;

    function resolve($path)
    {
        $this->called++;
        return new PHPTAL_StringSource("<p>found $path</p>");
    }
}

class MyCustomSourceResolver implements PHPTAL_SourceResolver
{
    function resolve($path)
    {
        return new MyCustomSource($path);
    }
}

class MyCustomSource implements PHPTAL_Source
{
    function __construct($path)
    {
        $this->path = $path;
    }

    function getRealPath()
    {
        return NULL;
    }

    function getLastModifiedTime()
    {
        if ($this->path === 'nocache') return mt_rand();
        return NULL;
    }

    function getData()
    {
        return '<p class="custom">'.$this->path.' '.mt_rand().'</p>';
    }
}

class CantFindAThing implements PHPTAL_SourceResolver
{
    function resolve($path)
    {
        return false;
    }
}

class SourceTest extends PHPTAL_TestCase
{
    function testResolver()
    {
        $tpl = $this->newPHPTAL()->addSourceResolver(new MyTestResolver())->setTemplate("testing123");
        $this->assertEquals('<p>found testing123</p>', $tpl->execute());
    }

    function testResolverCalledEachTime()
    {
        $tpl = $this->newPHPTAL()->addSourceResolver($r = new MyTestResolver());
        $this->assertEquals(0,$r->called);
        $tpl->setTemplate("testing123");
        $this->assertEquals('<p>found testing123</p>', $tpl->execute());
        $this->assertEquals(1,$r->called);

        $tpl->setTemplate("testing123");
        $this->assertEquals('<p>found testing123</p>', $tpl->execute());
        $this->assertEquals(2,$r->called);
    }

    function testCustomSource()
    {
        $tpl = $this->newPHPTAL()->addSourceResolver($r = new MyCustomSourceResolver());
        $tpl->setTemplate("xyz");

        $res = $tpl->execute();
        $this->assertContains('<p class="custom">xyz ', $res);

        // template should be cached
        $this->assertEquals($res, $tpl->execute());
        $tpl->setTemplate("xyz");
        $this->assertEquals($res, $tpl->execute());
    }


    function testCustomSourceCacheClear()
    {
        $tpl = $this->newPHPTAL()->addSourceResolver($r = new MyCustomSourceResolver());
        $tpl->setTemplate("nocache");

        $res = $tpl->execute();
        $this->assertContains('<p class="custom">nocache ', $res);

        // template should not be cached
        $this->assertEquals($res, $tpl->execute());
        $tpl->setTemplate("nocache");
        $this->assertNotEquals($res, $tpl->execute());
    }

    /**
     * @expectedException PHPTAL_IOException
     */
    function testFailsIfNotFound()
    {
        $tpl = $this->newPHPTAL()->addSourceResolver(new CantFindAThing())->setTemplate("something")->execute();
    }

    function testFallsBack()
    {
        $this->newPHPTAL()->addSourceResolver(new CantFindAThing())->setTemplate('input/phptal.01.html')->execute();
    }

    function testFallsBack2()
    {
        $this->newPHPTAL()->addSourceResolver(new CantFindAThing())->addSourceResolver(new CantFindAThing())->setTemplate('input/phptal.01.html')->execute();
    }

    function testFallsBack3()
    {
        $res = $this->newPHPTAL()->addSourceResolver(new CantFindAThing())->addSourceResolver(new MyTestResolver())->setTemplate('test')->execute();
        $this->assertEquals('<p>found test</p>', $res);
    }

    function testFallsBackToResolversFirst()
    {
        $res = $this->newPHPTAL()->addSourceResolver(new CantFindAThing())->addSourceResolver(new MyTestResolver())->setTemplate('input/phptal.01.html')->execute();
        $this->assertEquals('<p>found input/phptal.01.html</p>', normalize_html($res));
    }

    function testFallsBackToResolversFirst2()
    {
        $res = $this->newPHPTAL()->addSourceResolver(new MyTestResolver())->addSourceResolver(new CantFindAThing())->setTemplate('input/phptal.01.html')->execute();
        $this->assertEquals('<p>found input/phptal.01.html</p>', normalize_html($res));
    }

    function testOrder()
    {
        $res = $this->newPHPTAL()->addSourceResolver(new MyTestResolver())->addSourceResolver(new MyCustomSourceResolver())->setTemplate('test1')->execute();
        $this->assertEquals('<p>found test1</p>', normalize_html($res));
    }
}
