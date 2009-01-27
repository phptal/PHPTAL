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
require_once PHPTAL_DIR.'PHPTAL/Filter.php';

class MyPreFilter implements PHPTAL_Filter
{
    public function filter($str)
    {
        if (preg_match('|<root>(.*?)</root>|s', $str, $m)){
            return $m[1];
        }
        return $str;
    }
}

class MyPreFilter2 implements PHPTAL_Filter
{
    public function filter($str)
    {
        return preg_replace('/dummy/', '', $str);
    }
}

class PreFilterTest extends PHPUnit_Framework_TestCase
{
    function testIt()
    {
        $filter = new MyPreFilter();
        $tpl = new PHPTAL('input/prefilter.01.html');
        $tpl->setPreFilter($filter);
        $tpl->value = 'my value';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/prefilter.01.html');
        $this->assertEquals($exp, $res);
    }


    function testExternalMacro()
    {
        $filter = new MyPreFilter2();
        $tpl = new PHPTAL('input/prefilter.02.html');
        $tpl->setPreFilter($filter);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/prefilter.02.html');
        $this->assertEquals($exp, $res);
    }

    function testCache1()
    {
        $tpl = new PHPTAL('input/prefilter.03.html');
        $tpl->execute(); // compile and store version without prefilter

        $tpl = new PHPTAL('input/prefilter.03.html');
        $tpl->setPreFilter(new MyPreFilter2());
        $res = trim_string($tpl->execute());
        $exp = trim_string('<root>filtered</root>');
        $this->assertEquals($exp, $res);
    }
    
    function testCache2()
    {
        $tpl = new PHPTAL('input/prefilter.03.html');
        $tpl->execute(); // prepare version without prefilter

        $tpl->setPreFilter(new MyPreFilter2());
        $res = trim_string($tpl->execute());
        $exp = trim_string('<root>filtered</root>');
        $this->assertEquals($exp, $res);
    }
    
}
