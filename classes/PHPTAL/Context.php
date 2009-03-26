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
 * This class handles template execution context.
 * @package phptal
 */
class PHPTAL_Context
{
    public static $USE_GLOBAL = false;
    
    public $__line = false;
    public $__file = false;
    public $repeat;
    public $__xmlDeclaration;
    public $__docType;
    private $__nothrow;

    public function __construct()
    {
        $this->repeat = new StdClass();
    }

    public function __clone()
    {
        $this->repeat = clone($this->repeat);
    }

	public function setParent(PHPTAL_Context $parent)
	{
		$this->_parentContext = $parent;
	}

    public function setGlobal(StdClass $globalContext)
    {
        $this->_globalContext = $globalContext;
    }

    public function pushContext()
    {
        if (self::$USE_GLOBAL) return $this;
        $res = clone $this;
        $res->setParent($this);
        return $res;
    }

    public function popContext()
    {
        if (self::$USE_GLOBAL) return $this;
        return $this->_parentContext;
    }

    /** 
     * Set output document type if not already set.
     *
     * This method ensure PHPTAL uses the first DOCTYPE encountered (main
     * template or any macro template source containing a DOCTYPE.
     */
    public function setDocType($doctype)
    {
		if ($this->_parentContext != null){
			return $this->_parentContext->setDocType($doctype);
		}
        if ($this->_parentContext != null){
            return $this->_parentContext->setDocType($doctype);
        }
        if (!$this->__docType){
            $this->__docType = $doctype;
        }
    }

    /**
     * Set output document xml declaration.
     *
     * This method ensure PHPTAL uses the first xml declaration encountered
     * (main template or any macro template source containing an xml
     * declaration).
     */
    public function setXmlDeclaration($xmldec)
    {
		if ($this->_parentContext != null){
			return $this->_parentContext->setXmlDeclaration($xmldec);
		}
        if ($this->_parentContext != null){
            return $this->_parentContext->setXmlDeclaration($xmldec);
        }
        if (!$this->__xmlDeclaration){
            $this->__xmlDeclaration = $xmldec;
        }
    }

    /** 
     * Activate or deactivate exception throwing during unknown path
     * resolution.
     */
    public function noThrow($bool)
    {
        $this->__nothrow = $bool;
    }

    /**
     * Returns true if specified slot is filled.
     */
    public function hasSlot($key)
    {
		if ($this->_parentContext) return $this->_parentContext->hasSlot($key); // setting slots in any context
        return array_key_exists($key, $this->_slots);
    }

    /**
     * Returns the content of specified filled slot.
     */
    public function getSlot($key)
    {
		if ($this->_parentContext) return $this->_parentContext->getSlot($key); // setting slots in any context
        return $this->_slots[$key];
    }

    /**
     * Fill a macro slot.
     */
    public function fillSlot($key, $content)
    {
		if ($this->_parentContext) $this->_parentContext->fillSlot($key,$content); // setting slots in any context
		else $this->_slots[$key] = $content;
    }

    /**
     * Push current filled slots on stack.
     */
    public function pushSlots()
    {
        $this->_slotsStack[] =  $this->_slots;
        $this->_slots = array();
    }

    /**
     * Restore filled slots stack.
     */
    public function popSlots()
    {
        $this->_slots = array_pop($this->_slotsStack);
    }

    /**
     * Context setter.
     */
    public function __set($varname, $value)
    {
        if (preg_match('/^_|\s/',$varname))
        {
            throw new PHPTAL_InvalidVariableNameException('Template variable error \''.$varname.'\' must not begin with underscore or contain spaces');
        }
        $this->$varname = $value;
    }

   

    /**
     * Context getter.
     */
    public function __get($varname)
    {
        if (isset($this->$varname)){
            return $this->$varname;
        }

        if (isset($this->_globalContext->$varname)){
            return $this->_globalContext->$varname;
        }
        
        if (defined($varname))
        {
            return constant($varname);
        }
        
        if ($this->__nothrow)
            return null;
       
        throw new PHPTAL_VariableNotFoundException("Unable to find variable '$varname' in current scope", $this->__file, $this->__line);
    }

    private $_slots = array();
    private $_slotsStack = array();
	private $_parentContext = null;
    private $_globalContext = null;
}

// emulate property_exists() function, this is slow but much better than
// isset(), use next release of PHP5 as soon as available !
if (!function_exists('property_exists')){
    function property_exists($o, $property)
    {
        return array_key_exists($property, get_object_vars($o));
    }
}


/**
 * Resolve TALES path starting from the first path element.
 *
 * The TALES path : object/method1/10/method2
 * will call : phptal_path($ctx->object, 'method1/10/method2')
 *
 * The nothrow param is used by phptal_exists() and prevent this function to
 * throw an exception when a part of the path cannot be resolved, null is
 * returned instead.
 */
function phptal_path($base, $path, $nothrow=false)
{//{{{
	if ($base === null) 
	{
		if ($nothrow) return null;
		throw new PHPTAL_VariableNotFoundException("Trying to read property '$path' from NULL");
	}

    foreach(explode('/', $path) as $current)
    {
        // object handling
        if (is_object($base)){
            // look for method
            if (method_exists($base, $current)){
                $base = $base->$current();
                continue;
            }
            
            // look for variable
            if (property_exists($base, $current)){
                $base = $base->$current;
                continue;
            }
            
            if ($base instanceof ArrayAccess && $base->offsetExists($current))
            {
                $base = $base->offsetGet($current);
                continue;
            }
            
            if ($base instanceof Countable && ($current === 'length' || $current === 'size'))
            {
                $base = count($base);
                continue;
            }
                        
            // look for isset (priority over __get)
            if (method_exists($base,'__isset') && is_callable(array($base, '__isset')))
            {
                if ($base->__isset($current)){
                    $base = $base->$current;
                    continue;
                }
            }
            // ask __get and discard if it returns null
            else if (method_exists($base,'__get') && is_callable(array($base, '__get')))
            {
                $tmp = $base->$current;
                if (NULL !== $tmp){
                    $base = $tmp;
                    continue;
                }
            }

            // magic method call
            if (method_exists($base, '__call')) {
                try
                {
                    $base = $base->__call($current,array());
                    continue;
                }
                catch(BadMethodCallException $e){}
            }

            if ($nothrow)
                return null;

            phptal_path_error($base,$path,$current);
        }

        // array handling
        if (is_array($base)) 
        {
            // key or index
            if (array_key_exists((string)$current, $base)){
                $base = $base[$current];
                continue;
            }

            // virtual methods provided by phptal
            if ($current == 'length' || $current == 'size'){
                $base = count($base);
                continue;
            }

            if ($nothrow)
                return null;

            phptal_path_error($base,$path,$current);
        }

        // string handling
        if (is_string($base)) {
            // virtual methods provided by phptal
            if ($current == 'length' || $current == 'size'){
                $base = strlen($base);
                continue;
            }

            // access char at index
            if (is_numeric($current)){
                $base = $base[$current];
                continue;
            }
        }

        // if this point is reached, then the part cannot be resolved
        
        if ($nothrow)
            return null;
        
        phptal_path_error($base,$path,$current);
    }

    return $base;
}

/**
 * helper method for phptal_path(). Please don't use it directly.
 */
function phptal_path_error($base,$path,$current)
{
    $basename = '';
    if ($current !== $path) 
    {
        $pathinfo = " (in path '.../$path')";
        if (preg_match('!([^/]+)/'.preg_quote($current,'!').'(?:/|$)!',$path,$m))
        {
            $basename = "'".$m[1]."' ";
        }        
    }
    else $pathinfo = '';
        
    if (is_array($base)) throw new PHPTAL_VariableNotFoundException("Array {$basename}doesn't have key named '$current'$pathinfo");
    if (is_object($base)) throw new PHPTAL_VariableNotFoundException(ucfirst(get_class($base))." object {$basename}doesn't have method/property named '$current'$pathinfo");
    throw new PHPTAL_VariableNotFoundException(ucfirst(gettype($base))." {$basename}doesn't have property '$current'$pathinfo");    
}

function phptal_true($ctx, $path)
{
    $ctx->noThrow(true);
    $res = phptal_path($ctx, $path, true);
    $ctx->noThrow(false);
    return !!$res;
}

/** 
 * Returns true if $path can be fully resolved in $ctx context. 
 */
function phptal_exists($ctx, $path)
{
    // special note: this method may requires to be extended to a full
    // phptal_path() sibling to avoid calling latest path part if it is a
    // method or a function...
    $ctx->noThrow(true);
    $res = phptal_path($ctx, $path, true);
    $ctx->noThrow(false);
    return $res !== NULL;
}

function phptal_isempty($var)
{
	return $var === null || $var === false || $var === ''  
	       || ((is_array($var) || $var instanceof Countable) && count($var)===0);
}

function phptal_escape($var)
{
    if (is_string($var)) {
        return htmlspecialchars($var, ENT_QUOTES);
    }
    elseif (is_object($var)) {
        if ($var instanceof SimpleXMLElement) return $var->asXML();
        
        return htmlspecialchars((string)$var, ENT_QUOTES);
    }
    elseif (is_bool($var)){
        return (int)$var;
    }
    return $var;	
}
