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
require_once 'PHPTAL.php';

class PhptalPathTest_DummyClass
{
    public $foo;
}

class PhptalPathTest extends PHPUnit_Framework_TestCase
{
    function testZeroIndex()
    {
        $data   = array(1,0,3);
        $result = phptal_path($data, '0');
        $this->assertEquals(1, $result);
    }

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

        $tpl = new PHPTAL();
        $tpl->setSource($src, __FILE__);
        $tpl->o = new PhptalPathTest_DummyClass();
        $res = $tpl->execute();

        $this->assertEquals($exp, $res);
    }
}

?>
