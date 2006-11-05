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

define('PHPTAL_TALES_DEFAULT_KEYWORD', '_DEFAULT_DEFAULT_DEFAULT_DEFAULT_');
define('PHPTAL_TALES_NOTHING_KEYWORD', '_NOTHING_NOTHING_NOTHING_NOTHING_');


// TALES Specification 1.3
//
//      Expression  ::= [type_prefix ':'] String
//      type_prefix ::= Name
//
// Examples:
//
//      a/b/c
//      path:a/b/c
//      nothing
//      path:nothing
//      python: 1 + 2
//      string:Hello, ${username}
//
//
// Builtin Names in Page Templates (for PHPTAL)
//
//      * nothing - special singleton value used by TAL to represent a 
//        non-value (e.g. void, None, Nil, NULL).
//        
//      * default - special singleton value used by TAL to specify that 
//        existing text should not be replaced.
//
//      * repeat - the repeat variables (see RepeatVariable).
// 
function phptal_tales($expression, $nothrow=false)
{
    $expression = trim($expression);

    // Look for tales modifier (string:, exists:, etc...)
    //if (preg_match('/^([-a-z]+):(.*?)$/', $expression, $m)) {
    if (preg_match('/^([a-z][-.a-z]*[a-z]):(.*?)$/i', $expression, $m)) {
        list(,$typePrefix,$expression) = $m;
    }
    // may be a 'string'
    else if (preg_match('/^\'(.*?)\'$/', $expression, $m)) {
        list(,$expression) = $m;
        $typePrefix = 'string';
    }
    // failback to path:
    else {
        $typePrefix = 'path';
    }

    // is a registered TALES expression modifier
    if(PHPTAL_TalesRegistry::getInstance()->isRegistered($typePrefix)) {
    	$callback = PHPTAL_TalesRegistry::getInstance()->getCallback($typePrefix);
		return call_user_func($callback, $expression, $nothrow);
    }

    // class method
    if (strpos($typePrefix, '.')){
        $classCallback = explode('.', $typePrefix, 2);
        $callbackName  = NULL;
        if(!is_callable($classCallback, FALSE, $callbackName)) {
            $err = 'Unknown phptal modifier %s function %s does not exists or is not statically callable.';
            $err = sprintf($err, $typePrefix, $callbackName);
            throw new Exception($err);
        }
        $ref = new ReflectionClass($classCallback[0]);
        if(!$ref->implementsInterface('PHPTAL_Tales')){
            $err = 'Unable to use phptal modifier %s as the class %s does not implement the PHPTAL_Tales interface.';
            $err = sprintf($err, $typePrefix, $callbackName);
            throw new Exception($err);
        }
        return call_user_func($classCallback, $expression, $nothrow);
    }

    // its a function call   
    $func = 'phptal_tales_'.str_replace('-','_',$typePrefix);
    if (!function_exists($func)) {
        $err = 'Unknown phptal modifier %s function %s does not exists';
        $err = sprintf($err, $typePrefix, $func);
        throw new Exception($err);
    }
    return $func($expression, $nothrow);
}

// Register internal Tales expression modifiers
require_once 'PHPTAL/Php/TalesInternal.php';
PHPTAL_TalesInternal::registerInternalTales();
?>
