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
require_once 'PHPTAL/Trigger.php';

class MyTrigger implements PHPTAL_Trigger
{
    public $useCache = false;
    private $_cache  = false;
    
    public function start($id, $tpl)
    {
        if ($this->_cache){
            $this->useCache = true;
            return PHPTAL_Trigger::SKIPTAG;
        }
        
        $this->useCache = false;
        ob_start();
        return PHPTAL_Trigger::PROCEED;
    }
    
    public function end($id, $tpl)
    {
        if ($this->_cache == null){
            $this->_cache = ob_get_contents();
            ob_end_clean();
        }
        echo $this->_cache;
    }
}

class PhptalIdTest extends PHPUnit_Framework_TestCase
{
    function test01()
    {
        $trigger = new MyTrigger();
        
        $exp = trim_file('output/phptal.id.01.html');
        $tpl = new PHPTAL('input/phptal.id.01.html');
        $tpl->addTrigger('myTable', $trigger);
        $tpl->result = range(0,3);
        
        $res = $tpl->execute();
        $res = trim_string($res);

        $this->assertEquals($exp, $res);
        $this->assertEquals(false, $trigger->useCache);

        $res = $tpl->execute();
        $res = trim_string($res);
        
        $this->assertEquals($exp, $res);
        $this->assertEquals(true, $trigger->useCache);
    }
}

?>
