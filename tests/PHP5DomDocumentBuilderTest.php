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

class PHP5DOMDocumentBuilderTest extends PHPTAL_TestCase
{
    /**
     * @expectedException PHPTAL_ConfigurationException
     */
    function testRejectsNonUTF8()
    {
        $builder = new PHPTAL_Dom_PHP5DOMDocumentBuilder();
        $builder->setEncoding('ISO-8859-2');
    }

    private function parse($str)
    {
        $b = new PHPTAL_Dom_PHP5DOMDocumentBuilder();
        $p = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $p->parseString($b,$str);
        return $b->getResult();
    }

    private function parseUnparse($str)
    {
        $res = $this->parse($str);
        $this->assertType('DOMElement',$res);
        return $res->ownerDocument->saveXML($res);
    }

    function testPI()
    {
        $res = $this->parseUnparse($src = '<root><?php foo?></root>');
        $this->assertEquals($src,$res);

        $res = $this->parseUnparse($src = '<root>
            <?foo
        ?>
        </root>');
        $this->assertEquals(normalize_html($src),normalize_html($res));
    }

    function testCDATA()
    {
        $res = $this->parseUnparse($src = '<root><![CDATA[<foo>]]></root>');
        $this->assertEquals($src,$res);
    }

    function testTalNS()
    {
        $res = $this->parseUnparse($src = '<root xmlns:metal="http://xml.zope.org/namespaces/metal" xmlns:tal="http://xml.zope.org/namespaces/tal">
            <metal:block>x</metal:block><y tal:content="">y</y></root>');
        $this->assertEquals($src,$res);
    }


    /**
     * that's PHPTAL's hack
     */
    function testTalNSUndeclared()
    {
        $res = $this->parseUnparse($src = '<root>
            <metal:block>x</metal:block><y tal:content="">y</y></root>');

        $res = str_replace(' xmlns:metal="http://xml.zope.org/namespaces/metal"','',$res);
        $res = str_replace(' xmlns:tal="http://xml.zope.org/namespaces/tal"','',$res);

        $this->assertEquals($src,$res);
    }

    function testNS()
    {
        $res = $this->parseUnparse($src = '<root xmlns="foo:bar"/>');
        $this->assertEquals($src,$res);
    }

    function testRootNS()
    {
        $res = $this->parse('<root xmlns="urn:foo"/>');
        $this->assertType('DOMElement',$res);
        $this->assertEquals('',$res->prefix);
        $this->assertEquals('urn:foo',$res->namespaceURI);
    }

    function testContentNS()
    {
        $res = $this->parse('<root xmlns="urn:foo"><y/></root>');
        $this->assertType('DOMElement',$res);
        $this->assertType('DOMElement',$res->firstChild);
        $this->assertEquals('',$res->firstChild->prefix);
        $this->assertEquals('urn:foo',$res->firstChild->namespaceURI);
    }

    function testNSPrefix()
    {
        $res = $this->parseUnparse($src = '<x:root xmlns:x="foo:bar"><x:x x:z="a">a</x:x></x:root>');
        $this->assertEquals($src,$res);
    }

    function testRootNSPrefix()
    {
        $res = $this->parse('<x:root xmlns:x="urn:foo"/>');
        $this->assertType('DOMElement',$res);
        $this->assertEquals('x',$res->prefix);
        $this->assertEquals('urn:foo',$res->namespaceURI);
    }

    function testContentNSPrefix()
    {
        $res = $this->parse('<x:root xmlns:x="urn:foo"><x:y/></x:root>');
        $this->assertType('DOMElement',$res);
        $this->assertType('DOMElement',$res->firstChild);
        $this->assertEquals('x',$res->firstChild->prefix);
        $this->assertEquals('urn:foo',$res->firstChild->namespaceURI);
    }

    function testEntities()
    {
        $res = $this->parseUnparse($src = '<root>&amp;</root>');
        $this->assertEquals($src,$res);
    }
}
