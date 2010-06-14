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



class XmlParserTest extends PHPTAL_TestCase
{
    public function testSimpleParse()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseFile($builder = new MyDocumentBuilder(), 'input/xml.01.xml')->getResult();
        $expected = trim(join('', file('input/xml.01.xml')));
        $this->assertEquals($expected, $builder->result);
        $this->assertEquals(7, $builder->elementStarts);
        $this->assertEquals(7, $builder->elementCloses);
    }

    public function testXMLStylesheet()
    {
        $tpl = $this->newPHPTAL();
        $tpl->baz = 'quz';
        $tpl->setSource('<?xml version="1.0" encoding="utf-8"?>
        <?xml-stylesheet type="text/css" href="/css" ?>
        <?xml-stylesheet type="text/css" href="/${baz}" ?>
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl"/>');

        $this->assertXMLEquals('<?xml version="1.0" encoding="utf-8"?>
        <?xml-stylesheet type="text/css" href="/css" ?>
        <?xml-stylesheet type="text/css" href="/quz" ?>
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl"></html>', $tpl->execute());
    }

    public function testPHPBlocksNotInterpolated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<?php echo \'${baz}\' ?><html></html>');

        $this->assertEquals('${baz}<html></html>', $tpl->execute());
    }

    public function testCharactersBeforeBegining()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        try {
            $parser->parseFile($builder = new MyDocumentBuilder(), 'input/xml.02.xml')->getResult();
            $this->assertTrue( false );
        }
        catch (Exception $e)
        {
            $this->assertTrue( true );
        }
    }

    public function testAllowGtAndLtInTextNodes()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseFile($builder = new MyDocumentBuilder(), 'input/xml.03.xml')->getResult();

        $this->assertEquals(normalize_html_file('output/xml.03.xml'), normalize_html($builder->result));
        $this->assertEquals(3, $builder->elementStarts);
        $this->assertEquals(3, $builder->elementCloses);
        // a '<' character withing some text data make the parser call 2 times
        // the onElementData() method
        $this->assertEquals(7, $builder->datas);
    }


    /**
     * @expectedException PHPTAL_ParserException
     */
    public function testRejectsInvalidAttributes1()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(), '<foo bar="bar"baz="baz"/>')->getResult();
        $this->fail($builder->result);
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    public function testRejectsInvalidAttributes2()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(), '<foo bar;="bar"/>')->getResult();
        $this->fail($builder->result);
    }

    public function testSkipsBom()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(), "\xef\xbb\xbf<foo/>")->getResult();
        $this->assertEquals("<foo></foo>", $builder->result);
    }

    public function testAllowsTrickyQnames()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(), "\xef\xbb\xbf<_.:_ xmlns:_.='tricky'/>")->getResult();
        $this->assertEquals("<_.:_ xmlns:_.=\"tricky\"></_.:_>", $builder->result);
    }

    public function testRootNS()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(), "<f xmlns='foo:bar'/>")->getResult();
        $this->assertEquals('<f xmlns="foo:bar"></f>', $builder->result);
    }

    public function testAllowsXMLStylesheet()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $src = "<foo>
        <?xml-stylesheet href='foo1' ?>
        <?xml-stylesheet href='foo2' ?>
        </foo>";
        $parser->parseString($builder = new MyDocumentBuilder(), $src)->getResult();
        $this->assertEquals($src, $builder->result);
    }

    public function testFixOrRejectCDATAClose()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $src = '<a> ]]> </a>';
        try
        {
            $parser->parseString($builder = new MyDocumentBuilder(), $src)->getResult();
            $this->assertEquals('<a> ]]&gt; </a>', $builder->result);
        }
        catch(PHPTAL_ParserException $e)
        { /* ok - rejecting is one way to do it */ }
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    public function testSelfClosingSyntaxError()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $src = '<a / >';

        $parser->parseString($builder = new MyDocumentBuilder(), $src)->getResult();
    }

    public function testFixOrRejectEntities()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        $src = '<a href="?foo=1&bar=baz&copy=true&reg=x"> & ; &#x100; &nbsp; &#10; &--;</a>';
        try
        {
            $parser->parseString($builder = new MyDocumentBuilder(), $src)->getResult();
            $this->assertEquals('<a href="?foo=1&amp;bar=baz&amp;copy=true&amp;reg=x"> &amp; ; &#x100; &nbsp; &#10; &amp;--;</a>', $builder->result);
        }
        catch(PHPTAL_ParserException $e)
        { /* ok - rejecting is one way to do it */ }
    }

    public function testLineAccuracy()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_Dom_PHPTALDocumentBuilder(),
"<x>1

3
 4
<!-- 5 -->
            <x:y/> error in line 6!
            </x>
        ");
            $this->fail("Accepted invalid XML");
        }
        catch(PHPTAL_ParserException $e)
        {
            $this->assertEquals(6, $e->srcLine);
        }
    }

    public function testLineAccuracy2()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_Dom_PHPTALDocumentBuilder(),
"<x foo1='
2'

bar4='baz'

/>
<!------->


");
            $this->fail("Accepted invalid XML");
        }
        catch(PHPTAL_ParserException $e)
        {
            $this->assertEquals(7, $e->srcLine);
        }
    }

    public function testLineAccuracy3()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_Dom_PHPTALDocumentBuilder(),
                "

<x foo1='
2'

bar4='baz'

xxxx/>


");
            $this->fail("Accepted invalid XML");
        }
        catch(PHPTAL_ParserException $e)
        {
            $this->assertEquals(8, $e->srcLine);
        }
    }

    public function testClosingRoot()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_Dom_PHPTALDocumentBuilder(), "<imrootelement/></ishallnotbeclosed>");
            $this->fail("Accepted invalid XML");
        }
        catch(PHPTAL_ParserException $e)
        {
            $this->assertContains('ishallnotbeclosed', $e->getMessage());
            $this->assertNotContains('imrootelement', $e->getMessage());
            $this->assertNotContains("documentElement", $e->getMessage());
        }
    }

    public function testNotClosing()
    {
        $parser = new PHPTAL_Dom_SaxXmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_Dom_PHPTALDocumentBuilder(), "<element_a><element_b><element_x/><element_c><element_d><element_e>");
            $this->fail("Accepted invalid XML");
        }
        catch(PHPTAL_ParserException $e)
        {
            $this->assertNotContains("documentElement", $e->getMessage());
            $this->assertRegExp("/element_e.*element_d.*element_c.*element_b.*element_a/", $e->getMessage());
        }
    }

    public function testSPRY()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<html>
        <body>
        <div metal:define-macro="SomeMacro" xmlns:spry="http://ns.adobe.com/spry" >
        <div spry:region="someSpryRegion">
          <p spry:if="\'{someRegion::element}\' != \'hello\'">{someSpryRegion::element}</p>
        </div>
        </div>
        </body>
        </html>');
        $tpl->prepare();
    }

    public function testSPRY2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->phptal_var = 'ok';
        $tpl->setSource('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:spry="http://ns.adobe.com/spry"><body><form>
        <input type="text" value="${phptal_var}" spry:if="i == 1" /></form></body></html>');

        $this->assertXMLEquals('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xmlns:spry="http://ns.adobe.com/spry"><body><form>
        <input type="text" value="ok" spry:if="i == 1"/></form></body></html>', $tpl->execute());
    }

}

class MyDocumentBuilder extends PHPTAL_Dom_DocumentBuilder
{
    public $result;
    public $elementStarts = 0;
    public $elementCloses = 0;
    public $specifics = 0;
    public $datas = 0;
    public $allow_xmldec = true;

    public function __construct()
    {
        $this->result = '';
        parent::__construct();
    }

    public function onDoctype($dt)
    {
        $this->specifics++;
        $this->allow_xmldec = false;
        $this->result .= $dt;
    }

    public function onXmlDecl($decl)
    {
        if (!$this->allow_xmldec) throw new Exception("more than one xml decl");
        $this->specifics++;
        $this->allow_xmldec = false;
        $this->result .= $decl;
    }

    public function onCDATASection($data)
    {
        $this->onProcessingInstruction('<![CDATA['.$data.']]>');
    }

    public function onProcessingInstruction($data)
    {
        $this->specifics++;
        $this->allow_xmldec = false;
        $this->result .= $data;
    }

    public function onComment($data)
    {
        $this->onProcessingInstruction('<!--'.$data.'-->');
    }

    public function onElementStart($name, array $attributes)
    {
        $this->allow_xmldec = false;
        $this->elementStarts++;
        $this->result .= "<$name";
        $pairs = array();
        foreach ($attributes as $key=>$value) $pairs[] =  "$key=\"$value\"";
        if (count($pairs) > 0) {
            $this->result .= ' ' . join(' ', $pairs);
        }
        $this->result .= '>';
    }

    public function onElementClose($name)
    {
        $this->allow_xmldec = false;
        $this->elementCloses++;
        $this->result .= "</$name>";
    }

    public function onElementData($data)
    {
        $this->datas++;
        $this->result .= $data;
    }

    public function onDocumentStart(){}
    public function onDocumentEnd(){}
    public function getResult(){return $this->result;}
    public function setEncoding($e) {}
}


