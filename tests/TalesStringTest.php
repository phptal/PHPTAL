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
require_once 'PHPTAL/Tales.php';

class TalesStringTest extends PHPUnit2_Framework_TestCase 
{
    function testSimple()
    {
        $this->assertEquals('\'this is a string\'', phptal_tales_string('this is a string'));
    }

    function testDoubleDollar()
    {
        $this->assertEquals('\'this is a $string\'', phptal_tales_string('this is a $$string'));
    }

    function testSubPathSimple()
    {
        $res = phptal_tales_string('hello $name how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->name.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPath()
    {
        $res = phptal_tales_string('${name}');
        $rgm = preg_match('/\'\'\s*?\.*\$ctx->name.*?\'\'$/', $res);
        $this->assertEquals(1, $rgm);
    }

    function testSubPathExtended()
    {
        $res = phptal_tales_string('hello ${user/name} how are you ?');
        $rgm = preg_match('/\'hello \'.*?\$ctx->user, \'name\'.*?\' how are you \?\'$/', $res);
        $this->assertEquals(1, $rgm);
    }
}

?>
