<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
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

if (!class_exists('OnErrorDummyObject')) {
    class OnErrorDummyObject 
    {
        function throwException()
        {
            throw new Exception('error thrown');
        }
    }
}

class TalOnErrorTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-on-error.01.html');
        $tpl->dummy = new OnErrorDummyObject();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-on-error.01.html');
        $this->assertEquals($exp, $res);
        $this->assertEquals(1, count($tpl->errors));
        $this->assertEquals('error thrown', $tpl->errors[0]->getMessage());
    }

    function testEmpty()
    {
        $tpl = new PHPTAL('input/tal-on-error.02.html');
        $tpl->dummy = new OnErrorDummyObject();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-on-error.02.html');
        $this->assertEquals(1, count($tpl->errors));
        $this->assertEquals('error thrown', $tpl->errors[0]->getMessage());
        $this->assertEquals($exp, $res);
    }

    function testReplaceStructure()
    {
        $tpl = new PHPTAL('input/tal-on-error.03.html');
        $tpl->dummy = new OnErrorDummyObject();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-on-error.03.html');
        $this->assertEquals(1, count($tpl->errors));
        $this->assertEquals('error thrown', $tpl->errors[0]->getMessage());
        $this->assertEquals($exp, $res);        
    }
}
        
?>
