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


class PhptalTest extends PHPTAL_TestCase 
{
    function test01()
    {
        $tpl = $this->newPHPTAL('input/phptal.01.html');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testXmlHeader()
    {
        $tpl = $this->newPHPTAL('input/phptal.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/phptal.02.html');
        $this->assertEquals($exp, $res);
    }

    function testExceptionNoEcho()
    {
        $tpl = $this->newPHPTAL('input/phptal.03.html');
        ob_start();
        try {
            $res = $tpl->execute();
        }
        catch (Exception $e){
        }
        $c = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('', $c);
    }

    function testRepositorySingle()
    {
        $tpl = $this->newPHPTAL('phptal.01.html');
        $tpl->setTemplateRepository('input');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testRepositorySingleWithSlash()
    {
        $tpl = $this->newPHPTAL('phptal.01.html');
        $tpl->setTemplateRepository('input/');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testRepositoryMuliple()
    {
        $tpl = $this->newPHPTAL('phptal.01.html');
        $tpl->setTemplateRepository(array('bar', 'input/'));
        $tpl->setOutputMode(PHPTAL::XML);
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testSetTemplate()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setTemplateRepository(array('bar', 'input/'));
        $tpl->setOutputMode(PHPTAL::XML);
        $tpl->setTemplate('phptal.01.html');
        $res = $tpl->execute();
        $this->assertEquals('<dummy/>', $res);
    }

    function testXmlMode()
    {
        $tpl = $this->newPHPTAL('input/xml.04.xml');
        $tpl->setOutputMode(PHPTAL::XML);
        $res = trim_string($tpl->execute());
        $exp = trim_file('input/xml.04.xml');
        $this->assertEquals($exp, $res);
    }

    function testSource()
    {
        $source = '<span tal:content="foo"/>';
        $tpl = $this->newPHPTAL();
        $tpl->foo = 'foo value';
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->assertEquals('<span>foo value</span>', $res);
        $this->assertRegExp('/^tpl_/', $tpl->getFunctionName());
        $this->assertContains(PHPTAL_VERSION, $tpl->getFunctionName());
        $this->assertContains(md5('<string> '.md5($source) . '-'.PHPTAL::XHTML), $tpl->getFunctionName());
    }

    function testSourceWithPath()
    {
        $source = '<span tal:content="foo"/>';
        $tpl = $this->newPHPTAL();
        $tpl->foo = 'foo value';
        $tpl->setSource($source, '123');
        $res = $tpl->execute();
        $this->assertEquals('<span>foo value</span>', $res);
        $this->assertRegExp('/^tpl_/', $tpl->getFunctionName());
        $this->assertContains(PHPTAL_VERSION, $tpl->getFunctionName());
        $this->assertContains(md5('123-'.PHPTAL::XHTML), $tpl->getFunctionName());
    }

    function testStripComments()
    {
        $tpl = $this->newPHPTAL('input/phptal.04.html');
        $exp = trim_file('output/phptal.04.html');
        $tpl->stripComments(true);
        $res = $tpl->execute();
        $res = trim_string($res);
        $this->assertEquals($exp, $res);
    }

    function testUnknownOutputMode()
    {
        try {
            $tpl = $this->newPHPTAL();
            $tpl->setOutputMode('unknown');
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e){
            $this->assertTrue(true);
        }
    }

    function testZeroedContent()
    {
        $tpl = $this->newPHPTAL('input/phptal.05.html');
        $res = $tpl->execute();
        $exp = trim_file('input/phptal.05.html');
        $this->assertEquals($exp, $res);
    }

    function testOnlineExpression()
    {
        $tpl = $this->newPHPTAL('input/phptal.06.html');
        $tpl->foo = '<p>hello</p>';
        $res = $tpl->execute();
        $exp = trim_file('output/phptal.06.html');
        $this->assertEquals($exp,$res);
    }
}
        
?>
