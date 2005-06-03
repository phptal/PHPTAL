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

/** 
 * @package phptal
 */
abstract class PHPTAL_NamespaceAttribute
{
    /** 
     * @param $name string The attribute name
     * @param $priority int Attribute execution priority
     */
    public function __construct($name, $priority)
    {
        $this->_name = $name;
        $this->_priority = $priority;
    }

    /**
     * @return string
     */
    public function getName()
    { 
        return $this->_name; 
    }

    public function getFullName()
    {
        return $this->_namespace->getName() . ':' . $this->_name;
    }
    
    public function getPriority(){ return $this->_priority; }
    public function getNamespace(){ return $this->_namespace; }
    public function setNamespace(PHPTAL_Namespace $ns){ $this->_namespace = $ns; }

    public function createAttributeHandler(PHPTAL_Php_Element $tag, $expression)
    {
        return $this->_namespace->createAttributeHandler($this, $tag, $expression);
    }
    
    private $_name;         /* Attribute name without the namespace: prefix */
    private $_priority;     /* [0 - 1000] */
    private $_namespace;    /* PHPTAL_Namespace */
}

/** 
 * @package phptal
 */
class PHPTAL_NamespaceAttributeSurround extends PHPTAL_NamespaceAttribute 
{
    public function __construct($name, $priority)
    {
        parent::__construct($name, $priority);
    }
}

/** 
 * @package phptal
 */
class PHPTAL_NamespaceAttributeReplace extends PHPTAL_NamespaceAttribute 
{
    public function __construct($name, $priority)
    {
        parent::__construct($name, $priority);
    }
}

/** 
 * @package phptal
 */
class PHPTAL_NamespaceAttributeContent extends PHPTAL_NamespaceAttribute 
{
    public function __construct($name, $priority)
    {
        parent::__construct($name, $priority);
    }
}

/** 
 * @package phptal
 */
abstract class PHPTAL_Namespace
{
    public $xmlns;
    public $name;

    public function __construct($name, $xmlns)
    {
        $this->_attributes = array();
        $this->name = $name;
        $this->xmlns = $xmlns;
    }

    public function getName()
    {
        return $this->name;
    }

    public function hasAttribute($attributeName)
    {
        return array_key_exists(strtolower($attributeName), $this->_attributes);
    }

    public function getAttribute($attributeName)
    {
        return $this->_attributes[strtolower($attributeName)];
    }
    
    public function addAttribute(PHPTAL_NamespaceAttribute $attribute)
    {
        $attribute->setNamespace($this);
        $this->_attributes[strtolower($attribute->getName())] = $attribute;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    abstract public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_Php_Element $tag, $expression);

    protected $_attributes;
}

/** 
 * @package phptal
 */
class PHPTAL_BuiltinNamespace extends PHPTAL_Namespace
{
    public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_Php_Element $tag, $expression)
    {
        $name = $att->getName();
        $name = str_replace('-', '', $name);
        
        $class = 'PHPTAL_Php_Attribute_'.$this->getName().'_'.$name;
        $result = new $class();
        $result->tag = $tag;
        $result->name = strtoupper($att->getFullName());
        $result->expression = $expression;
        return $result;
    }
}

?>
