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
require_once PHPTAL_DIR.'Dom/Parser.php';
require_once PHPTAL_DIR.'Php/CodeWriter.php';
require_once PHPTAL_DIR.'Php/Attribute/TAL/Comment.php';

if (!class_exists('DummyTag')) {
    class DummyTag {}
}

class TalConditionTest extends PHPUnit_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-condition.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-condition.01.html');
        $this->assertEquals($exp, $res);
    }

    function testNot()
    {
        $tpl = new PHPTAL('input/tal-condition.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-condition.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testExists()
    {
        $tpl = new PHPTAL('input/tal-condition.03.html');
        $tpl->somevar = true;
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-condition.03.html');
        $this->assertEquals($exp, $res);        
    }

    function testException()
    {
        $tpl = new PHPTAL('input/tal-condition.04.html');
        $tpl->somevar = true;
        try {
            $tpl->execute();
        }
        catch (Exception $e){
        }
        $this->assertEquals(true, isset($e));
        // $exp = trim_file('output/tal-condition.04.html');
        // $this->assertEquals($exp, $res);        
    }
    
    function testChainedFalse()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('<tal:block tal:condition="foo | bar | baz | nothing">fail!</tal:block>');
        $res = $tpl->execute();
        $this->assertEquals($res,'');
    }
    
    function testChainedTrue()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('<tal:block tal:condition="foo | bar | baz | \'ok!\'">ok</tal:block>');
        $res = $tpl->execute();
        $this->assertEquals($res,'ok');
}
        
    function testChainedShortCircuit()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('<tal:block tal:condition="foo | \'ok!\' | bar | nothing">ok</tal:block>');
        $res = $tpl->execute();
        $this->assertEquals($res,'ok');
    }
}
