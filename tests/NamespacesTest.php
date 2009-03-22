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

class NamespacesTest extends PHPTAL_TestCase
{
    function testTalAlias()
    {
        $exp = trim_file('output/namespaces.01.html');
        $tpl = new PHPTAL('input/namespaces.01.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $this->assertEquals($exp, $res);
    }

    function testInherit()
    {
        $exp = trim_file('output/namespaces.02.html');
        $tpl = new PHPTAL('input/namespaces.02.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $this->assertEquals($exp, $res);
    }

    function testOverwrite()
    {
        $exp = trim_file('output/namespaces.03.html');
        $tpl = new PHPTAL('input/namespaces.03.html');
        $res = $tpl->execute();
        $res = trim_string($res);
        $this->assertEquals($exp, $res);
    }
    
    function testOverwriteBuiltinNamespace()
    {
        $tpl = new PHPTAL();
        $tpl->setSource($src='<metal:block xmlns:metal="non-zope" metal:use-macro="just kidding">ok</metal:block>');
        $this->assertEquals($src, $tpl->execute());
    }
    
    function testNamespaceWithoutPrefix()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('<metal:block xmlns:metal="non-zope">
                           <block xmlns="http://xml.zope.org/namespaces/tal" content="string:works" />                           
                         </metal:block>');
        $this->assertEquals(trim_string('<metal:block xmlns:metal="non-zope"> works </metal:block>'), 
                            trim_string($tpl->execute()));
    }
    
    function testRedefineBuiltinNamespace()
    {
        $tpl = new PHPTAL();
        $tpl->setSource('<metal:block xmlns:metal="non-zope">
                           <foo:block xmlns="x" xmlns:foo="http://xml.zope.org/namespaces/tal" content="string:works" />
                           <metal:block xmlns="http://xml.zope.org/namespaces/i18n" xmlns:metal="http://xml.zope.org/namespaces/tal" metal:content="string:properly" />
                         </metal:block>');
        $this->assertEquals(trim_string('<metal:block xmlns:metal="non-zope"> works properly </metal:block>'), 
                            trim_string($tpl->execute()));
    }    
}