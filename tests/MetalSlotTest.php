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

class MetalSlotTest extends PHPUnit2_Framework_TestCase
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/metal-slot.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.01.html');
        $this->assertEquals($exp, $res);
    }

    function testRecusiveFill()
    {
        $tpl = new PHPTAL('input/metal-slot.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.02.html');
        $this->assertEquals($exp, $res);
    }

    function testBlock()
    {
        $tpl = new PHPTAL('input/metal-slot.03.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.03.html');
        $this->assertEquals($exp, $res);
    }

    function testFillAndCondition()
    {
        $tpl = new PHPTAL('input/metal-slot.04.html');
        $tpl->fillit = true;
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/metal-slot.04.html');
        $this->assertEquals($exp, $res);
    }
}

?>
