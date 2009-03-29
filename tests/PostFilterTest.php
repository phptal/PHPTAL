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

require_once PHPTAL_DIR.'PHPTAL/Filter.php';

class MyPostFilter implements PHPTAL_Filter
{
    public function filter($str)
    {
        if (preg_match('|<root>(.*?)</root>|s', $str, $m)){
            return $m[1];
        }
        return $str;
    }
}

class MyPostFilter2 implements PHPTAL_Filter
{
    public function filter($str)
    {
        return str_replace('test','test-filtered',$str);
    }
}

class PostFilterTest extends PHPTAL_TestCase
{
    function testIt()
    {
        $filter = new MyPostFilter();
        $tpl = $this->newPHPTAL('input/postfilter.01.html');
        $tpl->setPostFilter($filter);
        $tpl->value = 'my value';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/postfilter.01.html');
        $this->assertEquals($exp, $res);
    }
    
    function testMacro()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setPostFilter(new MyPostFilter2());
        $tpl->setSource('<x><y metal:define-macro="macro">test2</y>
        test1
        <z metal:use-macro="macro" />
        </x>
        ');
        $this->assertEquals(trim_string('<x>test-filtered1<y>test-filtered2</y></x>'),trim_string($tpl->execute()));
    }
}
