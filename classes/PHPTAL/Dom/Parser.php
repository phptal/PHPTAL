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
  
    public function __construct($input_encoding)
    {
        parent::__construct($input_encoding);
        $this->_xmlns = new PHPTAL_Dom_XmlnsState(array(), array());
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
        $this->pushNode(new PHPTAL_DOMDocumentType($doctype));
    }

    public function onXmlDecl($decl)
    {
        $this->pushNode(new PHPTAL_DOMXmlDeclaration($decl));
    }
    
    public function onComment($data)
    {
        if ($this->_stripComments) 
            return;
        $this->pushNode(new PHPTAL_DOMComment($data));
    }
    
    public function onSpecific($data)
    {
        $this->pushNode(new PHPTAL_DOMSpecific($data));
    }

    public function onElementStart($name, array $attributes)
    {        
        $this->_xmlns = $this->_xmlns->newElement($attributes);
        
        if (preg_match('/^([^:]+):/',$name,$m))
        {
            $namespace_uri = $this->_xmlns->prefixToNamespaceURI($m[1]);            
            if (false === $namespace_uri) throw new PHPTAL_ParserException("There is no namespace declared for prefix of element <$name>");
        }
        else 
        {
            $namespace_uri = $this->_xmlns->getCurrentDefaultNamespaceURI();
        }
        
        $attrnodes = array();
        foreach ($attributes as $qname=>$value) 
        {
            $local_name = $qname;
            if (preg_match('/^([^:]+):(.+)$/',$qname,$m))
            {
                $local_name = $m[2];
                $attr_namespace_uri = $this->_xmlns->prefixToNamespaceURI($m[1]);
                if (false === $attr_namespace_uri) throw new PHPTAL_ParserException("There is no namespace declared for prefix of attribute $qname of element <$name>");
            }
            else
            {
                $attr_namespace_uri = ''; // default NS. Attributes don't inherit namespace per XMLNS spec
            }

            if ($this->_xmlns->isHandledNamespace($attr_namespace_uri) && !$this->_xmlns->isValidAttributeNS($attr_namespace_uri, $local_name)) {
                $this->raiseError(self::ERR_UNSUPPORTED_ATTRIBUTE, $qname);
            }
      
            $attrnodes[] = new PHPTAL_DOMAttr($qname, $attr_namespace_uri, $value, $this->getEncoding());
        }
        
        $node = new PHPTAL_DOMElement($name, $namespace_uri, $this->getXmlnsState(), $attrnodes);
        $this->pushNode($node);
        $this->_stack[] =  $this->_current;
        $this->_current = $node;
    }
    
    public function onElementData($data)
    {
        $this->pushNode(new PHPTAL_DOMText($data));
    }

    public function onElementClose($name)
    {
		if (!$this->_current instanceof PHPTAL_DOMElement) $this->raiseError("Found closing tag for '$name' where there are no open tags");			
        if ($this->_current->getQualifiedName() != $name) {
            $this->raiseError(self::ERR_ELEMENT_CLOSE_MISMATCH, $this->_current->getQualifiedName(), $name);
        }
        $this->_current = array_pop($this->_stack);
        if ($this->_current instanceOf PHPTAL_DOMElement)
            $this->_xmlns = $this->_current->getXmlnsState();
    }

    private function pushNode(PHPTAL_DOMNode $node)
    {
        $node->setSource($this->getSourceFile(), $this->getLineNumber());
        $this->_current->appendChild($node);
    }
    
    private $_tree;    /* PHPTAL_Dom_Parser_NodeTree */
    private $_stack;   /* array<PHPTAL_Dom_Parser_Node> */
    private $_current; /* PHPTAL_Dom_Parser_Node */
    private $_xmlns;   /* PHPTAL_Dom_Parser_XmlnsState */
    private $_stripComments = false;
}

?>
