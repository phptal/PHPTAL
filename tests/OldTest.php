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
require_once 'PHPTAL/Parser.php';

class OldTest extends PHPUnit2_Framework_TestCase 
{
    function test03()
    {
        $tpl = new PHPTAL('input/old-03.html');
        $tpl->title = 'My dynamic title';
        $tpl->content = '<p>my content</p>';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-03.html');
        $this->assertEquals($exp, $res);
    }

    function test06()
    {
        $tpl = new PHPTAL('input/old-06.html');
        $tpl->title = 'my title';
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-06.html');
        $this->assertEquals($exp, $res);        
    }

    function test08()
    {
        $tpl = new PHPTAL('input/old-08.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-08.html');
        $this->assertEquals($exp, $res);                
    }

    function test11()
    {
        $tpl = new PHPTAL('input/old-11.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-11.html');
        $this->assertEquals($exp, $res);                
    }

    function test12()
    {
        $tpl = new PHPTAL('input/old-12.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-12.html');
        $this->assertEquals($exp, $res);                
    }

    function test13()  // default keyword
    {
        $tpl = new PHPTAL('input/old-13.html');
        $l = new StdClass(); // DummyTag();
        $l->href= "http://www.example.com";
        $l->title = "example title";
        $l->name = "my link content";
        $tpl->a2 = "a value";
        $tpl->link2 = $l;
        
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-13.html');
        $this->assertEquals($exp, $res);                
    }

    function test15() // boolean attributes 
    {
        $tpl = new PHPTAL('input/old-15.html');
        $tpl->checked = true;
        $tpl->notchecked = false;
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-15.html');
        $this->assertEquals($exp, $res);
    }

    function test16() // default in attributes
    {
        $tpl = new PHPTAL('input/old-16.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-16.html');
        $this->assertEquals($exp, $res);
    }

    function test17() // test indents
    {
        $tpl = new PHPTAL('input/old-17.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-17.html');
        $this->assertEquals($exp, $res);
    }


    function test19() // attribute override
    {
        $tpl = new PHPTAL('input/old-19.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-19.html');
        $this->assertEquals($exp, $res);
    }


    function test20() // remove xmlns:tal, xmlns:phptal, xmlns:metal, xmlns:i18n
    {
        $tpl = new PHPTAL('input/old-20.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-20.html');
        $this->assertEquals($exp, $res);        
    }


    function test21() // ensure xhtml reduced tags are reduced
    {
        $tpl = new PHPTAL('input/old-21.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-21.html');
        $this->assertEquals($res, $exp);
    }


    function test23() // test custom modifier
    {
        $tpl = new PHPTAL('input/old-23.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-23.html');
        $this->assertEquals($res, $exp);
    }


    function test29() // test doctype inherited from macro
    {
        $tpl = new PHPTAL('input/old-29.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-29.html');
        $this->assertEquals($exp, $res);
    }

    function test30() // test blocks
    {
        $tpl = new PHPTAL('input/old-30.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-30.html');
        $this->assertEquals($exp, $res);
    }

    function test31() // test path evals
    {
        $a = new stdclass;
        $a->fooval = new stdclass;
        $a->fooval->b = new stdclass;
        $a->fooval->b->barval = "it's working";
        
        $tpl = new PHPTAL('input/old-31.html');
        $tpl->a = $a;
        $tpl->foo = 'fooval';
        $tpl->bar = 'barval';
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/old-31.html');
        $this->assertEquals($exp, $res);
    }

    function test32() // recursion
    {
        $o = array(
            'title' => 'my object',
            'children' => array(
                array('title' => 'o.1', 'children'=>array(
                    array('title'=>'o.1.1', 'children'=>array()),
                    array('title'=>'o.1.2', 'children'=>array()),
                      )),
                array('title' => 'o.2', 'children'=>array()),
            )
        );
        
        $tpl = new PHPTAL('input/old-32.html');
        $tpl->object = $o;
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/old-32.html');
        $this->assertEquals($exp, $res);
    }
}


function phptal_tales_my_modifier( $arg, $nothrow )
{
    return "strtoupper('$arg')";
}

?>
