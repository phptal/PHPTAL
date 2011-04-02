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



class DOMTest extends PHPTAL_TestCase
{
    private function newElement($name = 'foo', $ns = '')
    {
        $xmlns = new PHPTAL_Dom_XmlnsState(array(), '');
        return new PHPTAL_Dom_Element($name, $ns, array(), $xmlns);
    }

    function testAppendChild()
    {
        $el1 = $this->newElement();
        $el2 = $this->newElement();

        $this->assertInternalType('array', $el1->childNodes);
        $this->assertNull($el2->parentNode);

        $el1->appendChild($el2);
        $this->assertNull($el1->parentNode);
        $this->assertSame($el1, $el2->parentNode);
        $this->assertEquals(1, count($el1->childNodes));
        $this->assertTrue(isset($el1->childNodes[0]));
        $this->assertSame($el2, $el1->childNodes[0]);
    }

    function testAppendChildChangesParent()
    {
        $el1 = $this->newElement();
        $el2 = $this->newElement();

        $ch = $this->newElement();

        $el1->appendChild($ch);

        $this->assertTrue(isset($el1->childNodes[0]));
        $this->assertSame($ch, $el1->childNodes[0]);

        $el2->appendChild($ch);

        $this->assertTrue(isset($el2->childNodes[0]));
        $this->assertSame($ch, $el2->childNodes[0]);

        $this->assertFalse(isset($el1->childNodes[0]));

        $this->assertEquals(0, count($el1->childNodes));
        $this->assertEquals(1, count($el2->childNodes));
    }

    function testRemoveChild()
    {
        $el1 = $this->newElement();
        $el2 = $this->newElement();
        $el3 = $this->newElement();
        $el4 = $this->newElement();

        $el1->appendChild($el2);
        $el1->appendChild($el3);
        $el1->appendChild($el4);

        $this->assertEquals(3, count($el1->childNodes));
        $this->assertTrue(isset($el1->childNodes[2]));
        $this->assertFalse(isset($el1->childNodes[3]));

        $this->assertSame($el1, $el4->parentNode);

        $el1->removeChild($el4);

        $this->assertNull($el4->parentNode);

        $this->assertEquals(2, count($el1->childNodes));
        $this->assertTrue(isset($el1->childNodes[1]));
        $this->assertFalse(isset($el1->childNodes[2]));
        $this->assertSame($el3, end($el1->childNodes));

        $el1->removeChild($el2);

        $this->assertEquals(1, count($el1->childNodes));
        $this->assertTrue(isset($el1->childNodes[0]));
        $this->assertFalse(isset($el1->childNodes[1]));

    }

    function testReplaceChild()
    {
        $el1 = $this->newElement();
        $el2 = $this->newElement();
        $el3 = $this->newElement();
        $el4 = $this->newElement();

        $r = $this->newElement();

        $el1->appendChild($el2);
        $el1->appendChild($el3);
        $el1->appendChild($el4);

        $this->assertEquals(3, count($el1->childNodes));
        $this->assertSame($el3, $el1->childNodes[1]);

        $el1->replaceChild($r, $el3);

        $this->assertEquals(3, count($el1->childNodes));
        $this->assertSame($el2, $el1->childNodes[0]);
        $this->assertSame($r, $el1->childNodes[1]);
        $this->assertSame($el4, $el1->childNodes[2]);

        $this->assertNull($el3->parentNode);
        $this->assertSame($el1, $r->parentNode);
    }

    function testSetAttributeNS()
    {
        $el = $this->newElement();

        $this->assertEquals("", $el->getAttributeNS('urn:foons', 'bar'));
        $this->assertNull($el->getAttributeNodeNS('urn:foons', 'bar'));
        $el->setAttributeNS('urn:foons', 'bar', 'b\\az&<x>');
        $this->assertEquals('b\\az&<x>', $el->getAttributeNS('urn:foons', 'bar'));
        $this->assertNotNull($el->getAttributeNodeNS('urn:foons', 'bar'));
    }

    function testSetAttributeNSPrefixed()
    {
        $el = $this->newElement();

        $el->setAttributeNS('urn:foons', 'xab:bar', 'b\\az&<x>');
        $this->assertEquals('b\\az&<x>', $el->getAttributeNS('urn:foons', 'bar'));
        $this->assertNotNull($el->getAttributeNodeNS('urn:foons', 'bar'));
    }
}
