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
 * Stores XMLNS aliases fluctuation in the xml flow.
 *
 * This class is used to bind a PHPTAL namespace to an alias, for example using
 * xmlns:t="http://xml.zope.org/namespaces/tal" and later use t:repeat instead 
 * of tal:repeat.
 *
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_XmlnsState 
{
    /** Create a new XMLNS state inheriting provided aliases. */
    public function __construct(array $prefix_to_prefix, array $prefix_to_uri, $current_default = '')
    {
        $this->prefix_to_prefix = $prefix_to_prefix;
        $this->prefix_to_uri = $prefix_to_uri; 
        $this->current_default = $current_default; 
    }
    
    public function prefixToNamespaceURI($prefix)
    {
        if ($prefix === 'xmlns') return 'http://www.w3.org/2000/xmlns/';
        if ($prefix === 'xml') return 'http://www.w3.org/XML/1998/namespace';
        
        // domdefs provides fallback for all known phptal ns
        return isset($this->prefix_to_uri[$prefix]) ? $this->prefix_to_uri[$prefix] : PHPTAL_Dom_Defs::getInstance()->prefixToNamespaceURI($prefix);
    }

    /** Returns true if $attName is a valid attribute name, false otherwise. */
    public function isValidAttributeNS($namespace_uri, $local_name)
    {
        return PHPTAL_Dom_Defs::getInstance()->isValidAttributeNS($namespace_uri, $local_name);
    }
    
    public function isHandledNamespace($namespace_uri)
    {
        return PHPTAL_Dom_Defs::getInstance()->isHandledNamespace($namespace_uri);   
    }
    
    /** Returns the unaliased name of specified attribute. */
    public function unAliasAttribute($attName)
    {
        if (count($this->prefix_to_prefix) == 0) 
            return $attName;
        
        $result = $attName;
        foreach ($this->prefix_to_prefix as $prefix => $real){
            $result = str_replace("$prefix:", "$real:", $result);
        }
        return $result;
    }
    
    /** 
     * Returns a new XmlnsState inheriting of $this if $nodeAttributes contains 
     * xmlns attributes, returns $this otherwise.
     *
     * This method is used by the PHPTAL parser to keep track of xmlns fluctuation for
     * each encountered node.
     */
    public function newElement(array $nodeAttributes)
    {
        $prefix_to_prefix = $this->prefix_to_prefix;
        $prefix_to_uri = $this->prefix_to_uri;
        $current_default = $this->current_default;
        
        $changed = false;
        foreach ($nodeAttributes as $qname => $value)
        {
            if (preg_match('/^xmlns:(.+)$/', $qname, $m))
            {                
                $changed = true;
                list(,$prefix) = $m;
                $prefix_to_uri[$prefix] = $value;
                if (PHPTAL_Dom_Defs::getInstance()->isHandledXmlNs($qname, $value))
                {                
                    $prefix_to_prefix[$prefix] = PHPTAL_Dom_Defs::getInstance()->namespaceURIToPrefix($value);
                }                
            }
            
            if ($qname == 'xmlns') {$changed=true;$current_default = $value;}
        }
        
        if ($changed) 
        {
            return new PHPTAL_Dom_XmlnsState($prefix_to_prefix, $prefix_to_uri, $current_default);
        }
        else
        {
            return $this;
        }
    }
    
    function getCurrentDefaultNamespaceURI()
    {
        return $this->current_default;
    }

    private $prefix_to_prefix, $prefix_to_uri, $current_default;
}
