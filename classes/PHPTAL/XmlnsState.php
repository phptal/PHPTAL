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
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_XmlnsState 
{
    public function __construct($aliases = array())
    {
        $this->_aliases = $aliases;
    }

    public function isValidAttribute($attName)
    {
        $unaliased = $this->unAliasAttribute($attName);
        return PHPTAL_Defs::isValidAttribute($unaliased);
    }

    public function isPhpTalAttribute($attName)
    {
        $unaliased = $this->unAliasAttribute($attName);
        return PHPTAL_Defs::isPhpTalAttribute($unaliased);
    }

    public function getAttributePriority($attName)
    {
        $unaliased = $this->unAliasAttribute($attName);
        return PHPTAL_Defs::$RULES_ORDER[ strtoupper($unaliased) ];
    }

    public static function newElement($currentState, $attributes)
    {
        $aliases = array();
        foreach ($attributes as $att => $value){
            if (PHPTAL_Defs::isHandledXmlNs($att, $value)){
                preg_match('/^xmlns:(.*?)$/', $att, $m);
                list(,$alias) = $m;
                $aliases[$alias] = PHPTAL_Defs::$XMLNS[$value];
            }
        }
        if (count($aliases) > 0){
            // inherit aliases with maybe an overwrite
            $aliases = array_merge($currentState->_aliases, $aliases);
            return new PHPTAL_XmlnsState($aliases);
        }
        return $currentState;
    }

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

    private $_aliases;
}


?>
