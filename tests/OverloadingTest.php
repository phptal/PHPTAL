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

class OverloadTestClass
{
    public $vars = array('foo'=>'bar', 'baz'=>'biz');
    
    public function __set( $name, $value )
    {
        $this->vars[$name] = $value;
    }
    
    public function __get( $name )
    {
        if (array_key_exists($name, $this->vars))
            return $this->vars[$name];
        return null;
    }

    public function __isset( $key )
    {
        return isset($this->$key) || array_key_exists($key, $this->vars);
    }
    
    public function __call( $func, $args )
    {
        return "$func()=".join(',', $args);
    }
}


class OverloadingTest extends PHPUnit_Framework_TestCase 
{
    function test()
    {
        $tpl = new PHPTAL('input/overloading-01.html');
        $tpl->object = new OverloadTestClass();
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/overloading-01.html');
        $this->assertEquals($exp, $res);
    }
}

?>
