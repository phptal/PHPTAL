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


class PHPTAL_TestBuilder extends PHPTAL_Dom_DocumentBuilder
{
    public function getResult() {return $this->result;}

    public function onDocumentStart() { $this->result .= 'start'; }

    public function onDocumentEnd() { $this->result .= 'end'; }

    public function onDocType($doctype) { $this->result .= "[d:$doctype]"; }

    public function onXmlDecl($decl) { $this->result .= "[x:$decl]"; }

    public function onComment($data) { $this->result .= "[-:$data]"; }

    public function onCDATASection($data) { $this->result .= "[C:$data]"; }

    public function onProcessingInstruction($data) { $this->result .= "[?:$data]"; }

    public function onElementStart($element_qname, array $attributes)
    {
        $this->result .= "[$element_qname";
        foreach($attributes as $q=>$v) {
            $this->result .= ' '.$q.'='.$v;
        }
        $this->result .="]";
    }

    public function onElementData($data) { $this->result .= "'$data'"; }

    public function onElementClose($qname) { $this->result .= "[/$qname]"; }

    public function setEncoding($foo) {}
}

class PHP5ConverterTest extends PHPTAL_TestCase
{
    private function assertReparses($source)
    {
        list($doc,$tree) = $this->parseWithBuilder($source, new PHPTAL_Dom_PHP5DOMDocumentBuilder());
        $this->assertType('DOMElement',$tree);

        $res = $tree->ownerDocument->saveXML();
        $this->assertEquals(normalize_html($source), normalize_html($res));
    }

    private function assertParsesTo($expected, $source)
    {
        list($doc,$res) = $this->parseWithBuilder($source, new PHPTAL_TestBuilder());
        $this->assertEquals($expected, $res, "Original: ".$doc->saveXML());
    }

    const RESOLVE_EXTERNALS = false;
    private function parseWithBuilder($source, PHPTAL_Dom_DocumentBuilder $builder)
    {
        $doc = new DOMDocument();
        $doc->resolveExternals = self::RESOLVE_EXTERNALS;
        $this->assertTrue($doc->loadXML($source));

        // DOM doesn't store XML Decl exactly, so convertDocument requires it given separately
        if (preg_match('/^<\?xml.*?\?>/',$source,$m)) $xmldecl = $m[0]; else $xmldecl = '';

        $con = new PHPTAL_Dom_PHP5DOMConverter($builder);
        $build = $con->convertDocument($doc->documentElement,$xmldecl);

        $this->assertType('PHPTAL_Dom_DocumentBuilder',$build);
        return array($doc,$build->getResult());
    }

    function testDOCTYPE()
    {
        $this->assertParsesTo('start[d:<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">][html][/html]end',
        '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
            <html></html>
        ');
    }

    function testDOCTYPEShort()
    {
        $this->assertReparses('<?xml version="1.0"?><!DOCTYPE html>
            <html/>
        ');
    }

    function testRootNSPrefix()
    {
        $this->assertParsesTo("start[xhtmlz:script xmlns:xhtmlz=http://www.w3.org/1999/xhtml]'foo'[/xhtmlz:script]end",
            '<xhtmlz:script xmlns:xhtmlz="http://www.w3.org/1999/xhtml">foo</xhtmlz:script>');
    }

    function testRootNSPrefix2()
    {
        $this->assertReparses('<?xml version="1.0" encoding="UTF-8"?><xhtmlz:script xmlns:xhtmlz="http://www.w3.org/1999/xhtml">foo</xhtmlz:script>');
    }

    function testUnusedNS()
    {
        $this->markTestSkipped(); // DOM doesn't preserve xmlns.

        $this->assertParsesTo("start[html xmlns:unused=http://foo.example.org/foo/foo][p]'This exemple '[y xmlns=urn:x][/y]'should remove xmlns for tal and metal but must keep the foo namespace.'[/p][/html]end",
        '<html xmlns:unused="http://foo.example.org/foo/foo"><p>This exemple <y xmlns="urn:x"/>should remove xmlns for tal and metal but must keep the foo namespace.</p></html>
        ');
    }

    function testEscape()
    {
        if (self::RESOLVE_EXTERNALS) $this->markTestSkipped(); // adds xml:space to style

        $this->assertParsesTo("start[d:<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">][html]'
	'[head]'
		'[title]'Foo'[/title]'
		'[style type=text/css]'
			@import(\"/foo.css\");
		'[/style]'
	'[/head]'
	'[body]'
	\"\"
	>
	Bar
	&lt;
	Foo
	'[/body]'
'[/html]end",str_replace("<\n","&lt;\n",file_get_contents('input/escape.html')));
    }

    function testXMLDecl()
    {
        $this->assertParsesTo('start[x:<?xml version="1.0" encoding="utf-8" standalone="yes"?>][x][/x]end','<?xml version="1.0" encoding="utf-8" standalone="yes"?><x/>');
    }

    function testXMLDeclNoStandalone()
    {
        $this->assertParsesTo('start[x:<?xml version="1.0" encoding="utf-8" standalone="no"?>][x][/x]end','<?xml version="1.0" encoding="utf-8" standalone="no"?><x/>');
    }

    function testNoXMLDecl()
    {
        $this->assertParsesTo('start[x][/x]end','<x/>');
    }

    function testDOCTYPENoXMLDecl()
    {
        if (!self::RESOLVE_EXTERNALS) $this->markTestSkipped();

        $this->assertParsesTo('startx[x][/x]end','<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"><xhtml>&nbsp;</xhtml>');
    }

    function testEntityRef()
    {
        $doc = new DOMDocument('1.0','UTF-8');
        $doc->appendChild($doc->createElement('root'));
        $doc->documentElement->appendChild($doc->createEntityReference('nbsp'));
        $doc->documentElement->appendChild($doc->createTextNode('&nbsp;'));

        $builder = new PHPTAL_TestBuilder();
        $con = new PHPTAL_Dom_PHP5DOMConverter($builder);
        $build = $con->convertDocument($doc->documentElement,'');

        $this->assertEquals("start[root]'&nbsp;''&amp;nbsp;'[/root]end", $build->getResult());
    }
}
