<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
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

require_once 'PHPTAL/Defs.php';
require_once 'PHPTAL/Node.php';
require_once 'PHPTAL/XmlParser.php';
require_once 'PHPTAL/Tales.php';

/**
 * Template parser.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @package PHPTAL
 */
class PHPTAL_Parser extends PHPTAL_XmlParser
{
    const ERR_DOCUMENT_END_STACK_NOT_EMPTY =
        "Reached document end but element stack not empty";
    const ERR_UNSUPPORTED_ATTRIBUTE = 
        "Unsupported attribute '%s'";
    const ERR_ELEMENT_CLOSE_MISMATCH = 
        "Tag closure mismatch, expected '%s' but was '%s'";
  
    public function __construct( $codeGenerator )
    {
        $this->_codeGenerator = $codeGenerator;
    }

    public function getGenerator()
    {
        return $this->_codeGenerator;
    }
    
    public function parseString( $str ) 
    {
        parent::parseString( $str );
        return $this->_tree;
    }
    
    public function parseFile( $path )
    {
        parent::parseFile( $path );
        return $this->_tree;
    }

    public function onDocumentStart()
    {
        $this->_tree = new PHPTAL_NodeTree($this);
        $this->_stack = array();
        $this->_current = $this->_tree;
    }
    
    public function onDocumentEnd()
    {
        if (count($this->_stack) > 0) {
            $this->raiseError(self::ERR_DOCUMENT_END_STACK_NOT_EMPTY);
        }
    }

    public function onDocType($doctype)
    {
        $node = new PHPTAL_NodeDocType($this, $doctype);
        array_push($this->_current->children, $node);
    }

    public function onXmlDecl($decl)
    {
        $node = new PHPTAL_NodeDocType($this, $decl);
        array_push($this->_current->children, $node);
    }
    
    public function onElementStart($name, $attributes)
    {
        foreach ($attributes as $key=>$value) {
            if (!PHPTAL_Defs::isValidAttribute($key)) {
                $err = sprintf( self::ERR_UNSUPPORTED_ATTRIBUTE, $key );
                $this->raiseError($err);
            }
        }
        
        $node = new PHPTAL_NodeElement($this, $name, $attributes);
        array_push($this->_current->children, $node);
        array_push($this->_stack, $this->_current);
        $this->_current = $node;
    }
    
    public function onElementClose($name)
    {
        if ($this->_current->name != $name) {
            $err = sprintf(self::ERR_ELEMENT_CLOSE_MISMATCH, $this->_current->name, $name);
            $this->raiseError($err);
        }
        $this->_current = array_pop($this->_stack);        
    }
    
    public function onElementData($data)
    {
        $node = new PHPTAL_NodeText($this, $data);
        array_push($this->_current->children, $node);
    }
    
    public function onSpecific($data)
    {
        $node = new PHPTAL_NodeSpecific($this, $data);
        array_push($this->_current->children, $node);
    }

    private $_tree;
    private $_stack;
    private $_current;
    private $_codeGenerator;
}

?>
