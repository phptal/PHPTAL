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

require_once dirname(__FILE__)."/config.php";

class MyPHP5DOMPreFilter extends PHPTAL_PreFilter
{
    public $node;

    public function filterElement(DOMElement $node)
    {
        $this->node = $node->cloneNode(true);
    }
}

class MyPHPTALDomPreFilter extends PHPTAL_PreFilter
{
}

class PreFilterTest extends PHPTAL_TestCase
{
    function testPHP5DOMNotNeeded()
    {
        $pre = new MyPHPTALDomPreFilter();

        $this->assertFalse($pre->isPHP5DOMNeeded());
    }

    function testPHP5DOMNeeded()
    {
        $pre = new MyPHP5DOMPreFilter();

        $this->assertTrue($pre->isPHP5DOMNeeded());
    }


    function testPHP5DOMParser()
    {
        $source = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><root foo="ö"><node/></root>';

        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $tpl->setPreFilter($pre = new MyPHP5DOMPreFilter());
        $tpl->prepare();

        $this->assertNotNull($pre->node);
        $this->assertEquals(normalize_html($source), normalize_html($pre->node->ownerDocument->saveXML()));
    }

    function testPreservesNamespace()
    {
        $res = $this->newPHPTAL()->setSource('<html xmlns="http://www.w3.org/1999/xhtml"><title/></html>')
            ->addPreFilter($pre = new MyPHP5DOMPreFilter())->execute();

        $this->assertXMLEquals('<html xmlns="http://www.w3.org/1999/xhtml"><title/></html>',$res);
        $this->assertEquals("http://www.w3.org/1999/xhtml", $pre->node->namespaceURI);
    }
    
    
    function testPreservesUnprefixedAttributes()
    {
        $res = $this->newPHPTAL()->setSource($src = '<html xmlns="http://www.w3.org/1999/xhtml"><head manifest=""/></html>')
            ->addPreFilter($pre = new MyPHP5DOMPreFilter())->execute();
            
        $this->assertXMLEquals($src, $res);
        $this->assertEquals("http://www.w3.org/1999/xhtml", $pre->node->namespaceURI);
    }
}
