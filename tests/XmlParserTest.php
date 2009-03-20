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

class XmlParserTest extends PHPTAL_TestCase
{
    public function testSimpleParse(){
        $parser = new MyTestParser();
        $parser->parseFile('input/xml.01.xml');
        $expected = trim(join('', file('input/xml.01.xml')));
        $this->assertEquals($expected, $parser->result);
        $this->assertEquals(7, $parser->elementStarts);
        $this->assertEquals(7, $parser->elementCloses);
    }

    public function testCharactersBeforeBegining() {
        $parser = new MyTestParser();
        try {
            $parser->parseFile('input/xml.02.xml');
            $this->assertTrue( false );
        }
        catch (Exception $e) {
            $this->assertTrue( true );
        }
    }

    public function testAllowGtAndLtInTextNodes() {
        $parser = new MyTestParser();
        $parser->parseFile('input/xml.03.xml');
        $expected = trim(join('', file('input/xml.03.xml')));
        $this->assertEquals($expected, $parser->result);
        $this->assertEquals(3, $parser->elementStarts);
        $this->assertEquals(3, $parser->elementCloses);
        // a '<' character withing some text data make the parser call 2 times
        // the onElementData() method
        $this->assertEquals(7, $parser->datas);
    }
}


class MyTestParser extends PHPTAL_XmlParser
{
    public $result;
    public $elementStarts = 0;
    public $elementCloses = 0;
    public $specifics = 0;
    public $datas = 0;

    public function __construct() {
        $this->result = '';
        parent::__construct();
    }

    public function onDoctype($dt) {
        $this->specifics++;
        $this->result .= $dt;
    }

    public function onXmlDecl($decl){
        $this->specifics++;
        $this->result .= $decl;
    }
    
    public function onSpecific($data) { 
        $this->specifics++;
        $this->result .= $data; 
    }

    public function onComment($data) {
        $this->onSpecific($data);
    }
    
    public function onElementStart($name, $attributes) {
        $this->elementStarts++;
        $this->result .= "<$name";
        $pairs = array();
        foreach ($attributes as $key=>$value) $pairs[] =  "$key=\"$value\"";
        if (count($pairs) > 0) {
            $this->result .= ' ' . join(' ', $pairs);
        }
        $this->result .= '>';
    }
    
    public function onElementClose($name){
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
