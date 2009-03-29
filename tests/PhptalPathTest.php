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


class PhptalPathTest_DummyClass
{
    public $foo;
}

/* protected get/isset doesn't work in PHP 5.3 
class PhptalPathTest_DummyIssetClass
{    
    protected function __isset($isset)
    {
        return false;
    }
}

class PhptalPathTest_DummyGetClass
{   
    protected function __get($anything)
    {
        return 'whatever';
    }
}
*/

class PhptalPathTest extends PHPTAL_TestCase
{
    function testZeroIndex()
    {
        $data   = array(1,0,3);
        $result = phptal_path($data, '0');
        $this->assertEquals(1, $result);
    }
    
    /* protected get/isset doesn't work in PHP 5.3 
    function testProtectedIsset()
    {
        $tpl = $this->newPHPTAL(); 
        $tpl->protected = new PhptalPathTest_DummyIssetClass;
        $tpl->setSource('<p tal:content="protected/fail | \'ok\'"></p>');
        $res = $tpl->execute();
        $this->assertEquals($res,'<p>ok</p>');
    }
    
    function testProtectedGet()
    {
        $tpl = $this->newPHPTAL(); 
        $tpl->protected = new PhptalPathTest_DummyGetClass;
        $tpl->setSource('<p tal:content="protected/fail | \'ok\'"></p>');
        $res = $tpl->execute();
        $this->assertEquals($res,'<p>ok</p>');
    }    
    */
    
    function testDefinedButNullProperty()
    {
        $src = <<<EOS
<span tal:content="o/foo"/>
<span tal:content="o/foo | string:blah"/>
<span tal:content="o/bar" tal:on-error="string:ok"/>
EOS;
        $exp = <<<EOS
<span></span>
<span>blah</span>
ok
EOS;

        $tpl = $this->newPHPTAL();
        $tpl->setSource($src, __FILE__);
        $tpl->o = new PhptalPathTest_DummyClass();
        $res = $tpl->execute();

        $this->assertEquals($exp, $res);
    }
}

?>
