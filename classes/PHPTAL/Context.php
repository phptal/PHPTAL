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

class PHPTAL_Context
{
    public $__line = false;
    public $__file = false;
    public $__repeat;
    public $__xmlDeclaration;
    public $__docType;
    public $__nothrow;
    public $__translator;

    public function __construct()
    {
        $this->__repeat = new StdClass();
    }

    public function __clone()
    {
        $this->__repeat = clone($this->__repeat);
    }

    public function setDocType($doctype)
    {
        if (!$this->__docType){
            $this->__docType = $doctype;
        }
    }

    public function setXmlDeclaration($xmldec)
    {
        if (!$this->__xmlDeclaration){
            $this->__xmlDeclaration = $xmldec;
        }
    }

    public function noThrow($bool)
    {
        $this->__nothrow = $bool;
    }

    public function hasSlot($key)
    {
        return array_key_exists($key, $this->_slots);
    }

    public function getSlot($key)
    {
        return $this->_slots[$key];
    }

    public function fillSlot($key, $content)
    {
        $this->_slots[$key] = $content;
    }

    public function pushSlots()
    {
        array_push($this->_slotsStack, $this->_slots);
        $this->_slots = array();
    }

    public function popSlots()
    {
        $this->_slots = array_pop($this->_slotsStack);
    }

    public function __set($varname, $value)
    {
        if ($varname[0] == '_'){
            $e = 'Template variable error \'%s\' must not begin with underscore';
            $e = sprintf($e, $varname);
            throw new Exception($e);
        }
        $this->$varname = $value;
    }

    public function __get($varname)
    {
        if ($varname == 'repeat')
            return $this->__repeat;

        if (isset($this->$varname)){
            return $this->$varname;
        }
            
        if ($this->__nothrow)
            return null;
       
        $e = sprintf('Unable to find path %s', $varname); 
        throw new PHPTAL_Exception($e, $this->__file, $this->__line);
    }

    private $_slots = array();
    private $_slotsStack = array();
}

?>
