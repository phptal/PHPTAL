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

require_once PHPTAL_DIR.'PHPTAL/Dom/Defs.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/Node.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/XmlParser.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/XmlnsState.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

/**
 * Template parser.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Parser extends PHPTAL_XmlParser
{
    const ERR_DOCUMENT_END_STACK_NOT_EMPTY = "Not all elements were closed before end of the document (element stack not empty)";
    const ERR_UNSUPPORTED_ATTRIBUTE = "Unsupported attribute '%s'";
    const ERR_ELEMENT_CLOSE_MISMATCH = "Tag closure mismatch, expected '%s' but was '%s'";
  
    public function __construct($input_encoding = 'UTF-8')
    {
        parent::__construct($input_encoding);
        $this->_xmlns = new PHPTAL_Dom_XmlnsState();
    }

    public function getXmlnsState()
    {
        return $this->_xmlns;
    }

    public function stripComments($b)
    {
        $this->_stripComments = $b;
    }
    
    public function parseString($src, $filename = '<string>') 
    {
        parent::parseString($src, $filename);
        return $this->_tree;
    }
    
    public function parseFile($path)
    {
        parent::parseFile($path);
        return $this->_tree;
    }

    // ~~~~~ XmlParser implementation ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    
    public function onDocumentStart()
    {
        $this->_tree = new PHPTAL_Dom_Tree();
        $this->_tree->setSource($this->getSourceFile(), $this->getLineNumber());
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
        $this->pushNode(new PHPTAL_Dom_DocType($doctype));
    }

    public function onXmlDecl($decl)
    {
        $this->pushNode(new PHPTAL_Dom_XmlDeclaration($decl));
    }
    
    public function onComment($data)
    {
        if ($this->_stripComments) 
            return;
        $this->pushNode(new PHPTAL_Dom_Comment($data));
    }
    
    public function onSpecific($data)
    {
        $this->pushNode(new PHPTAL_Dom_Specific($data));
    }

    public function onElementStart($name, $attributes)
    {        
        $this->_xmlns = PHPTAL_Dom_XmlnsState::newElement($this->_xmlns, $attributes);
        
        foreach ($attributes as $key=>$value) {
            if (!$this->_xmlns->isValidAttribute($key)) {
                $this->raiseError(self::ERR_UNSUPPORTED_ATTRIBUTE, $key);
            }
        }
        
        $node = new PHPTAL_Dom_Element($name, $attributes);
        $node->setXmlnsState($this->getXmlnsState());
        $this->pushNode($node);
        array_push($this->_stack, $this->_current);
        $this->_current = $node;
    }
    
    public function onElementData($data)
    {
        $this->pushNode(new PHPTAL_Dom_Text($data));
    }

    public function onElementClose($name)
    {
		if (!$this->_current instanceof PHPTAL_Dom_Element) $this->raiseError("Found closing tag for '$name' where there are no open tags");			
        if ($this->_current->getName() != $name) {
            $this->raiseError(self::ERR_ELEMENT_CLOSE_MISMATCH, $this->_current->getName(), $name);
        }
        $this->_current = array_pop($this->_stack);
        if ($this->_current instanceOf PHPTAL_Dom_Element)
            $this->_xmlns = $this->_current->getXmlnsState();
    }

    private function pushNode(PHPTAL_Dom_Node $node)
    {
        $node->setSource($this->getSourceFile(), $this->getLineNumber());
        $this->_current->addChild($node);
    }
    
    private $_tree;    /* PHPTAL_Dom_Parser_NodeTree */
    private $_stack;   /* array<PHPTAL_Dom_Parser_Node> */
    private $_current; /* PHPTAL_Dom_Parser_Node */
    private $_xmlns;   /* PHPTAL_Dom_Parser_XmlnsState */
    private $_stripComments = false;
}

?>
