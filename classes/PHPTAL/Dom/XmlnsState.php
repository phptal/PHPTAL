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
    public function __construct($aliases = array())
    {
        assert(is_array($aliases));
        $this->_aliases = $aliases;
    }

    /** Returns true if $attName is a valid attribute name, false otherwise. */
    public function isValidAttribute($attName)
    {
        $unaliased = $this->unAliasAttribute($attName);
        return PHPTAL_Dom_Defs::getInstance()->isValidAttribute($unaliased);
    }

    /** Returns true if $attName is a PHPTAL attribute, false otherwise. */
    public function isPhpTalAttribute($attName)
    {
        $unaliased = $this->unAliasAttribute($attName);
        return PHPTAL_Dom_Defs::getInstance()->isPhpTalAttribute($unaliased);
    }

    /** Returns the unaliased name of specified attribute. */
    public function unAliasAttribute($attName)
    {
        if (count($this->_aliases) == 0) 
            return $attName;
        
        $result = $attName;
        foreach ($this->_aliases as $alias => $real){
            $result = str_replace("$alias:", "$real:", $result);
        }
        return $result;
    }
    
    /** 
     * Returns a new XmlnsState inheriting of $currentState if $nodeAttributes contains 
     * xmlns attributes, returns $currentState otherwise.
     *
     * This method is used by the PHPTAL parser to keep track of xmlns fluctuation for
     * each encountered node.
     */
    public static function newElement(PHPTAL_Dom_XmlnsState $currentState, $nodeAttributes)
    {
        $aliases = array();
        foreach ($nodeAttributes as $att => $value){
            if (PHPTAL_Dom_Defs::getInstance()->isHandledXmlNs($att, $value)){
                preg_match('/^xmlns:(.*?)$/', $att, $m);
                list(,$alias) = $m;
                $aliases[$alias] = PHPTAL_Dom_Defs::getInstance()->xmlnsToLocalName($value);
            }
        }
        if (count($aliases) > 0){
            // inherit aliases with maybe an overwrite
            $aliases = array_merge($currentState->_aliases, $aliases);
            return new PHPTAL_Dom_XmlnsState($aliases);
        }
        return $currentState;
    }

    private $_aliases;
}


?>
