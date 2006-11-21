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

class DummyObjectX{
    public function __contruct(){
        $this->_data = array();
    }
    public function __isset($var){
        return array_key_exists($var, $this->_data);
    }
    public function __get($var){
        return $this->_data[$var];
    }
    public function __set($var, $value){
        $this->_data[$var] = $value;
    }
    public function __call($method, $params){
        return '__call';
    }
    private $_data;
}

class TalesIssetNullTest extends PHPUnit_Framework_TestCase
{
    function testIt()
    {
        $dummy = new DummyObjectX();
        $dummy->foo = null;

        $res = phptal_path($dummy, 'method');
        $this->assertEquals('__call', $res);
        
        $res = phptal_path($dummy, 'foo');
        $this->assertEquals(null, $res);
    }
}

?>
