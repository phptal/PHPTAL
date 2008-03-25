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
require_once PHPTAL_DIR.'PHPTAL/Dom/Parser.php';
require_once PHPTAL_DIR.'PHPTAL/Php/CodeWriter.php';

class ParserTest extends PHPUnit_Framework_TestCase
{
    public function testParseSimpleDocument()
    {
        $parser = new PHPTAL_Dom_Parser();
        $tree = $parser->parseFile('input/parser.01.xml');

        if ($tree instanceof DOMNode) $this->markTestSkipped();

        $children = $tree->getChildren();
        $this->assertEquals(3, count($children));
        $this->assertEquals(5, count($children[2]->getChildren()));
    }

    public function testByteOrderMark()
    {
        $parser = new PHPTAL_Dom_Parser();
        try {
            $tree = $parser->parseFile('input/parser.02.xml');
            $this->assertTrue(true);
        }
        catch (Exception $e){
            $this->assertTrue(false);
        }
    }

    public function testBadAttribute(){
        try {
            $parser = new PHPTAL_Dom_Parser();
            $parser->parseFile('input/parser.03.xml');
        }
        catch (Exception $e){
            $this->assertTrue( preg_match('/attribute single or double quote/', $e->getMessage()) == 1 );
        }
    }

    public function testLegalElementNames()
    {
        $parser = new PHPTAL_Dom_Parser();
        $parser->parseString('<?xml version="1.0" encoding="UTF-8"?>
        <t1 xmlns:foo..._-ą="http://foo.example.com"><foo..._-ą:test-element_name /><t---- /><t___ /><oóźżćń /><d.... /></t1>');        
    }

    public function testIllegalElementNames1()
    {
        $parser = new PHPTAL_Dom_Parser();
        try
        {
            $parser->parseString('<?xml version="1.0" encoding="UTF-8"?>
            <t><1element /></t>');

    		$this->markTestIncomplete('Non-critical error waiting to be fixed');
//            $this->fail("Accepted invalid element name starting with a number");
        }
        catch(PHPTAL_Exception $e) {}
    }

    public function testIllegalElementNames2()
    {
        $parser = new PHPTAL_Dom_Parser();
        try
        {
            $parser->parseString('<t><element~ /></t>');
            $this->fail("Accepted invalid element name");
        }
        catch(PHPTAL_Exception $e) {}
    }

}
