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

class TalRepeatTest extends PHPUnit_Framework_TestCase 
{
    function testArrayRepeat()
    {
        $tpl = new PHPTAL('input/tal-repeat.01.html');
        $tpl->array = range(0,4);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.01.html');
        $this->assertEquals($exp, $res);
    }

    function testOddEventAndFriends()
    {
        $tpl = new PHPTAL('input/tal-repeat.02.html');
        $tpl->array = range(0,2);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testIterableUsage()
    {
        $tpl = new PHPTAL('input/tal-repeat.03.html');
        $tpl->result = new MyIterable(4);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-repeat.03.html');
        $this->assertEquals($exp, $res);        
    }

    function testHashKey()
    {
        $tpl = new PHPTAL('input/tal-repeat.04.html');
        $tpl->result = array('a'=>0, 'b'=>1, 'c'=>2, 'd'=>3);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-repeat.04.html');
        $this->assertEquals($exp, $res);                             
    }

    function testRepeatAttributesWithPhp()
    {
        $tpl = new PHPTAL('input/tal-repeat.05.html');
        $tpl->data = array(1,2,3);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.05.html');
        $this->assertEquals($exp, $res);
    }


    function testRepeatAttributesWithMacroPhp()
    {
        $tpl = new PHPTAL('input/tal-repeat.06.html');
        $tpl->data = array(1,2,3);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.06.html');
        $this->assertEquals($exp, $res);
    }


    function testPhpMode()
    {
        $tpl = new PHPTAL('input/tal-repeat.07.html');
        $tpl->result = array('a'=>0, 'b'=>1, 'c'=>2, 'd'=>3);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.07.html');
        $this->assertEquals($exp, $res);        
    }
    
    function testTraversableRepeat()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<a><b/><c/><d/><e/><f/><g/></a>');
                
        $tpl = new PHPTAL();
        $tpl->setSource('<tal:block tal:repeat="node nodes">${repeat/node/key}${node/tagName}</tal:block>');
        $tpl->nodes = $doc->getElementsByTagName('*');
        
        $this->assertEquals('0a1b2c3d4e5f6g',$tpl->execute());

    }
    
    function testLetter()
    {
        $tpl = new PHPTAL();
        $tpl->setSource( '<span tal:omit-tag="" tal:repeat="item items" tal:content="repeat/item/letter"/>' );
        $tpl->items = range( 0, 32 );
        $res = trim_string( $tpl->execute() );
        $exp = 'abcdefghijklmnopqrstuvwxyzaaabacadaeafag';
        $this->assertEquals( $exp, $res );
    }
    
    function testRoman()
    {
        $tpl = new PHPTAL();
        $tpl->setSource( '<span tal:omit-tag="" tal:repeat="item items" tal:content="string:${repeat/item/roman},"/>' );
        $tpl->items = range( 0, 16 );
        $res = trim_string( $tpl->execute() );
        $exp = 'i,ii,iii,iv,v,vi,vii,viii,ix,x,xi,xii,xiii,xiv,xv,xvi,xvii,';
        $this->assertEquals( $exp, $res );        
    }
    
    function testGrouping()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('
            <div tal:omit-tag="" tal:repeat="item items">
                <h1 tal:condition="repeat/item/first" tal:content="item"></h1>
                <p tal:condition="not: repeat/item/first" tal:content="item"></p>
                <hr tal:condition="repeat/item/last" />
            </div>'
        );
        $tpl->items = array( 'apple', 'apple', 'orange', 'orange', 'orange', 'pear', 'kiwi', 'kiwi' );
        $res = trim_string( $tpl->execute() );        
        $exp = trim_string('
            <h1>apple</h1>
            <p>apple</p>
            <hr/>
            <h1>orange</h1>
            <p>orange</p>
            <p>orange</p>
            <hr/>
            <h1>pear</h1>
            <hr/>
            <h1>kiwi</h1>
            <p>kiwi</p>
            <hr/>'
        );

        $this->assertEquals( $exp, $res );        
    }
    
    function testGroupingPath()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('
            <div tal:omit-tag="" tal:repeat="item items">
                <h1 tal:condition="repeat/item/first/type" tal:content="item/type"></h1>
                <p tal:content="item/name"></p>
                <hr tal:condition="repeat/item/last/type" />
            </div>'
        );
        $tpl->items = array(
                            array( 'type' => 'car', 'name' => 'bmw' ),
                            array( 'type' => 'car', 'name' => 'audi' ),
                            array( 'type' => 'plane', 'name' => 'boeing' ),
                            array( 'type' => 'bike', 'name' => 'suzuki' ),
                            array( 'type' => 'bike', 'name' => 'honda' ),
        );
        $res = trim_string( $tpl->execute() );        
        $exp = trim_string('
            <h1>car</h1>
            <p>bmw</p>
            <p>audi</p>
            <hr/>
            <h1>plane</h1>
            <p>boeing</p>
            <hr/>
            <h1>bike</h1>
            <p>suzuki</p>
            <p>honda</p>
            <hr/>'
        );

        $this->assertEquals( $exp, $res );        
    }    
}


class MyIterable implements Iterator
{
    public function __construct($size){
        $this->_index = 0;
        $this->_size= $size;
    }
    
    public function rewind(){
        $this->_index = 0;
    }
    
    public function current(){
        return $this->_index;
    }
    
    public function key(){
        return $this->_index;
    }
    
    public function next(){
        $this->_index++;
        return $this->_index;
    }
    
    public function valid(){
        return $this->_index < $this->_size;
    }

    public function size(){
        return $this->_size;
    }

    private $_index;
    private $_size;
}

?>
