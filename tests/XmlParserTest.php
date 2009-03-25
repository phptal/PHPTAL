<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

require_once 'config.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/XmlParser.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/DocumentBuilder.php';

class XmlParserTest extends PHPTAL_TestCase
{
    public function testSimpleParse(){
        $parser = new PHPTAL_XmlParser('UTF-8');
        $parser->parseFile($builder = new MyDocumentBuilder(),'input/xml.01.xml')->getResult();
        $expected = trim(join('', file('input/xml.01.xml')));
        $this->assertEquals($expected, $builder->result);
        $this->assertEquals(7, $builder->elementStarts);
        $this->assertEquals(7, $builder->elementCloses);
    }

    public function testCharactersBeforeBegining() {
        $parser = new PHPTAL_XmlParser('UTF-8');
        try {
            $parser->parseFile($builder = new MyDocumentBuilder(),'input/xml.02.xml')->getResult();
            $this->assertTrue( false );
        }
        catch (Exception $e) {
            $this->assertTrue( true );
        }
    }

    public function testAllowGtAndLtInTextNodes() {
        $parser = new PHPTAL_XmlParser('UTF-8');
        $parser->parseFile($builder = new MyDocumentBuilder(),'input/xml.03.xml')->getResult();
        $expected = trim(join('', file('input/xml.03.xml')));
        $this->assertEquals($expected, $builder->result);
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
        $parser = new PHPTAL_XmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(),'<foo bar="bar"baz="baz"/>')->getResult();
        $this->fail($builder->result);
    }
    
    /**
     * @expectedException PHPTAL_ParserException
     */
    public function testRejectsInvalidAttributes2()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(),'<foo bar;="bar"/>')->getResult();
        $this->fail($builder->result);
    }
    
    public function testSkipsBom()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(),"\xef\xbb\xbf<foo/>")->getResult();
        $this->assertEquals("<foo></foo>", $builder->result);
    }
        
    public function testAllowsTrickyQnames()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        $parser->parseString($builder = new MyDocumentBuilder(),"\xef\xbb\xbf<_.:_ xmlns:_.='tricky'/>")->getResult();
        $this->assertEquals("<_.:_ xmlns:_.=\"tricky\"></_.:_>", $builder->result);
    }
    
    public function testAllowsXMLStylesheet()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        $src = "<foo>
        <?xml-stylesheet href='foo1' ?>
        <?xml-stylesheet href='foo2' ?>
        </foo>";
        $parser->parseString($builder = new MyDocumentBuilder(),$src)->getResult();
        $this->assertEquals($src, $builder->result);        
    }
    
    public function testLineAccuracy()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_DOM_DocumentBuilder(),
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
            $this->assertEquals(6,$e->srcLine);
        }        
    }
    
    public function testLineAccuracy2()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_DOM_DocumentBuilder(),
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
            $this->assertEquals(7,$e->srcLine);
        }        
    }    
    
    public function testLineAccuracy3()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        try
        {
            $parser->parseString(new PHPTAL_DOM_DocumentBuilder(),
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
            $this->assertEquals(8,$e->srcLine);
        }        
    }    
    
}

class MyDocumentBuilder extends PHPTAL_DOM_DocumentBuilder
{
    public $result;
    public $elementStarts = 0;
    public $elementCloses = 0;
    public $specifics = 0;
    public $datas = 0;
    public $allow_xmldec = true;

    public function __construct() {
        $this->result = '';
        parent::__construct();
    }

    public function onDoctype($dt) {
        $this->specifics++;
        $this->allow_xmldec = false;
        $this->result .= $dt;
    }

    public function onXmlDecl($decl){
        if (!$this->allow_xmldec) throw new Exception("more than one xml decl");
        $this->specifics++;
        $this->allow_xmldec = false;
        $this->result .= $decl;
    }
    
    public function onOther($data) { 
        $this->specifics++;
        $this->allow_xmldec = false;        
        $this->result .= $data; 
    }

    public function onComment($data) {
        $this->allow_xmldec = false;        
        $this->onOther($data);
    }
    
    public function onElementStart($name, array $attributes) {
        $this->allow_xmldec = false;        
        $this->elementStarts++;
        $this->result .= "<$name";
        $pairs = array();
        foreach($attributes as $key=>$value) $pairs[] =  "$key=\"$value\"";
        if (count($pairs) > 0) {
            $this->result .= ' ' . join(' ', $pairs);
        }
        $this->result .= '>';
    }
    
    public function onElementClose($name){
        $this->allow_xmldec = false;        
        $this->elementCloses++;
        $this->result .= "</$name>";
    }
    
    public function onElementData($data){
        $this->datas++;
        $this->result .= $data;
    }
    
    public function onDocumentStart(){}
    public function onDocumentEnd(){
    }
}

?>
