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

require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Comment.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Replace.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Content.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Condition.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Attributes.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Repeat.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/Define.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/OnError.php';
require_once 'PHPTAL/PhpGenerator/Attribute/TAL/OmitTag.php';

require_once 'PHPTAL/PhpGenerator/Attribute/METAL/DefineMacro.php';
require_once 'PHPTAL/PhpGenerator/Attribute/METAL/UseMacro.php';
require_once 'PHPTAL/PhpGenerator/Attribute/METAL/DefineSlot.php';
require_once 'PHPTAL/PhpGenerator/Attribute/METAL/FillSlot.php';

require_once 'PHPTAL/PhpGenerator/Attribute/PHPTAL/Tales.php';
require_once 'PHPTAL/PhpGenerator/Attribute/PHPTAL/Debug.php';
require_once 'PHPTAL/PhpGenerator/Attribute/PHPTAL/Id.php';

require_once 'PHPTAL/PhpGenerator/Attribute/I18N/Translate.php';
require_once 'PHPTAL/PhpGenerator/Attribute/I18N/Name.php';
require_once 'PHPTAL/PhpGenerator/Attribute/I18N/Domain.php';
require_once 'PHPTAL/PhpGenerator/Attribute/I18N/Attributes.php';

require_once 'PHPTAL/Parser/Node.php';

/**
 * Base class for all PHPTAL attributes.
 *
 * Attributes are first ordered by PHPTAL then called depending on their
 * priority before and after the element printing.
 *
 * An attribute must implements start() and end().
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Attribute 
{
    const ECHO_TEXT = 'text';
    const ECHO_STRUCTURE = 'structure';
    
    /** Attribute name (ie: 'tal:content'). */
    public $name;
    /** Attribute value specified by the element. */
    public $expression;
    /** Element using this attribute (xml node). */
    public $tag;

    /** Attribute constructor. */
    public function __construct($tag)
    {//{{{
        $this->tag = $tag;
    }//}}}

    /** Called before element printing. */
    public abstract function start();
    /** Called after element printing. */
    public abstract function end();
    
    /**
     * Factory method, returns a new attribute instance.
     */
    public static function createAttribute($tag, $attName, $expression)
    {//{{{
        $class = 'PHPTAL_Attribute_' . str_replace(':','_', $attName);
        $class = str_replace('-', '', $class);
        
        $result = new $class($tag);
        $result->name = strtoupper($attName);
        $result->expression = $expression;
        return $result;
    }//}}}

    /**
     * Remove structure|text keyword from expression and stores it for later
     * doEcho() usage.
     *
     * $expression = 'stucture my/path';
     * $expression = $this->extractEchoType($expression);
     *
     * ...
     *
     * $this->doEcho($code);
     */
    protected function extractEchoType($expression)
    {
        $echoType = self::ECHO_TEXT;
        $expression = trim($expression);
        if (preg_match('/^(text|structure)\s+(.*?)$/ism', $expression, $m)) {
            list(, $echoType, $expression) = $m;
        }
        $this->_echoType = strtolower($echoType);
        return trim($expression);
    }

    protected function doEcho($code)
    {
        if ($this->_echoType == self::ECHO_TEXT)
            $this->tag->generator->doEcho($code);
        else
            $this->tag->generator->doEchoRaw($code);
    }

    protected function parseSetExpression($exp)
    {
        $exp = trim($exp);
        // (dest) (value)
        if (preg_match('/^([a-z0-9:\-_]+)\s+(.*?)$/i', $exp, $m)){
            array_shift($m);
            return $m;
        }
        // (dest)
        return array($exp, null);
    }

    private $_echoType = PHPTAL_Attribute::ECHO_TEXT;
}

?>
