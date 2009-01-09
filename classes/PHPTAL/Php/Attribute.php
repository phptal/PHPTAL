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

require_once PHPTAL_DIR.'PHPTAL/Dom/Node.php';

/**
 * Base class for all PHPTAL attributes.
 *
 * Attributes are first ordered by PHPTAL then called depending on their
 * priority before and after the element printing.
 *
 * An attribute must implements start() and end().
 * 
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Php_Attribute 
{
    const ECHO_TEXT = 'text';
    const ECHO_STRUCTURE = 'structure';
    
    /** Attribute name (ie: 'tal:content'). */
    public $name;
    /** Attribute value specified by the element. */
    public $expression;
    /** Element using this attribute (xml node). */
    public $tag;

    /** Called before element printing. */
    public abstract function start();
    /** Called after element printing. */
    public abstract function end();

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
        return array($exp, NULL);
    }

    protected $_echoType = PHPTAL_Php_Attribute::ECHO_TEXT;
}

?>
