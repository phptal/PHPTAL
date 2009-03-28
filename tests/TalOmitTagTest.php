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


class TalOmitTagTest extends PHPTAL_TestCase 
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/tal-omit-tag.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-omit-tag.01.html');
        $this->assertEquals($exp, $res);
    }

    function testWithCondition()
    {
        $tpl = $this->newPHPTAL('input/tal-omit-tag.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-omit-tag.02.html');
        $this->assertEquals($exp, $res);
    }
    
    private $call_count;
    function callCount()
    {
        $this->call_count++;
    }
    
    function testCalledOnlyOnce()
    {
        $this->call_count=0;
        $tpl = $this->newPHPTAL();        
        $tpl->setSource('<p tal:omit-tag="test/callCount" />');
        
        $tpl->test = $this;
        $tpl->execute();        
        $this->assertEquals(1,$this->call_count);

        $tpl->execute();
        $this->assertEquals(2,$this->call_count);
    }
    
    function testNestedConditions()
    {
        $this->call_count=0;
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<span tal:omit-tag="php:true">a<span tal:omit-tag="php:false">b<span tal:omit-tag="php:true">c<span tal:omit-tag="php:false">d<span tal:omit-tag="php:false">e<span tal:omit-tag="php:true">f<span tal:omit-tag="php:true">g</span>h</span>i</span>j</span>k</span></span></span>');
        
        $this->assertEquals('a<span>bc<span>d<span>efghi</span>j</span>k</span>',$tpl->execute());
    }
}
        
