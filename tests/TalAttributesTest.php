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

if (!class_exists('DummyTag')) {
    class DummyTag {}
}

class TalAttributesTest extends PHPTAL_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-attributes.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.01.html');
        $this->assertEquals($exp, $res);
    }

    function testWithContent()
    {
        $tpl = new PHPTAL('input/tal-attributes.02.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.02.html');
        $this->assertEquals($exp, $res);
    }

    function testMultiples()
    {
        $tpl = new PHPTAL('input/tal-attributes.03.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.03.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = new PHPTAL('input/tal-attributes.04.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.04.html');
        $this->assertEquals($exp, $res);
    }

    function testMultipleChains()
    {
        $tpl = new PHPTAL('input/tal-attributes.05.html');
        $tpl->spanClass = 'dummy';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.05.html');
        $this->assertEquals($exp, $res);
    }

    function testEncoding()
    {
        $tpl = new PHPTAL('input/tal-attributes.06.html');
        $tpl->href = "http://www.test.com/?foo=bar&buz=biz&<thisissomething";
        $tpl->title = 'bla bla <blabla>';
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-attributes.06.html');
        $this->assertEquals($exp, $res);
    }

    function testZeroValues()
    {
        $tpl = new PHPTAL('input/tal-attributes.07.html');
        $tpl->href1 = 0;
        $tpl->href2 = 0;
        $tpl->href3 = 0;
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-attributes.07.html');
        $this->assertEquals($exp, $res);
    }

    function testEmpty()
    {
        $src = <<<EOT
<span class="&quot;'default" tal:attributes="class nullv | falsev | emptystrv | zerov | default"></span>
EOT;
        $exp = <<<EOT
<span class="0"></span>
EOT;
        $tpl = new PHPTAL();
        $tpl->setSource($src, __FILE__);
        $tpl->nullv = null;
        $tpl->falsev = false;
        $tpl->emptystrv = '';
        $tpl->zerov = 0;
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testSingleQuote()
    {
        $exp = trim_file('output/tal-attributes.08.html');
        $tpl = new PHPTAL('input/tal-attributes.08.html');
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    { 
        $exp = trim_file('output/tal-attributes.09.html');
        $tpl = new PHPTAL('input/tal-attributes.09.html');
        $tpl->value = "return confirm('hel<lo');";
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    } 

    function testChainedStructure()
    {
        $exp = trim_file('output/tal-attributes.10.html');
        $tpl = new PHPTAL('input/tal-attributes.10.html');
        $tpl->value1 = false;
        $tpl->value2 = "return confirm('hel<lo');";
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    function testNothingValue()
    {
        $tpl = new PHPTAL(); 
        $tpl->setSource('<p tal:attributes="title missing | nothing"></p>');
        $res = $tpl->execute();
        $this->assertEquals($res,'<p></p>');
    }

    function testNULLValue()
    {
        $tpl = new PHPTAL(); 
        $tpl->setSource('<p tal:attributes="title missing | php:NULL"></p><p tal:attributes="class \'ok\'; title null:blah"></p>');
        $res = $tpl->execute();
        $this->assertEquals('<p></p><p class="ok"></p>',$res);
    }
    
    function testNULLValueNoAlternative()
    {
       $tpl = new PHPTAL(); 
       $tpl->setSource('<p tal:attributes="title php:NULL"></p>');
       $res = $tpl->execute();
       $this->assertEquals('<p></p>',$res);
    }

    function testNULLValueReversed()
    {
       $tpl = new PHPTAL(); 
       $tpl->setSource('<p tal:attributes="title php:true ? NULL : false; class structure php:false ? NULL : \'fo\\\'o\'; style structure php:true ? NULL : false;"></p>');
       $res = $tpl->execute();
       $this->assertEquals('<p class="fo\'o"></p>',$res);
    }
    
    function testEmptyValue()
    {
        $tpl = new PHPTAL(); 
        $tpl->setSource('<p tal:attributes="title missing | \'\'"></p><p tal:attributes="title missing | php:\'\'"></p>');
        $res = $tpl->execute();
        $this->assertEquals('<p title=""></p><p title=""></p>',$res);
    }
    
    function testSemicolon()
    {
        $tpl = new PHPTAL(); 
        $tpl->setSource('<div><p tal:content="\'\\\'a;b;;c;;;d\'" tal:attributes="style \'color:red;; font-weight:bold;;;;\'; title php:\'\\\'test;;test;;;;test\'"></p></div>');
        $res = $tpl->execute();
        $this->assertEquals($res,'<div><p style="color:red; font-weight:bold;;" title="&#039;test;test;;test">&#039;a;b;;c;;;d</p></div>');
    }
    
    //TODO: test xhtml boolean attributes (currently tested in 'old' tests)
}


function phptal_tales_null($code,$nothrow)
{
    return 'NULL';
}

