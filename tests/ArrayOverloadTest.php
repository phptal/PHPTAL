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

class MyArray
{
    public function push($value) {
        array_push($this->_values, $value);
    }
    
    public function __getAt($index){
        return $this->_values[$index];
    }

    public function __setAt($index, $value){
        $this->_values[$index] = $value;
    }

    private $_values = array();
}

class ArrayOverloadTest extends PHPUnit2_Framework_TestCase
{
    function testIt()
    {
        $arr = new MyArray();
        for ($i=0; $i<20; $i++){
            $val = new StdClass;
            $val->foo = "foo value $i";
            $arr->push($val);
        }

        $tpl = new PHPTAL('input/array-overload.01.html');
        $tpl->myobject = $arr;
        $res = $tpl->execute();
        $exp = trim_file('output/array-overload.01.html');
        $res = trim_string($res);
        $this->assertEquals($exp, $res);
    }
}

?>
