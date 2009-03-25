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

require_once PHPTAL_DIR.'PHPTAL/Dom/Defs.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Node.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/XmlParser.php';
require_once PHPTAL_DIR.'PHPTAL/Dom/XmlnsState.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

/**
 * DOM Builder
 */
class PHPTAL_DOM_DocumentBuilder implements PHPTAL_DocumentBuilder
{  
    public function __construct()
    {
        $this->_xmlns = new PHPTAL_Dom_XmlnsState(array(), '');
    }
    
    public function getResult()
    {
        return $this->_tree;
    }

    public function getXmlnsState()
    {
        return $this->_xmlns;
    }

    public function stripComments($b)
    {
        $this->_stripComments = $b;
    }
    
    // ~~~~~ XmlParser implementation ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    
    public function onDocumentStart()
    {
        $this->_tree = new PHPTAL_DOMElement('root','http://xml.zope.org/namespaces/tal',array(),$this->getXmlnsState());
        $this->_tree->setSource($this->file, $this->line);
        $this->_stack = array();
        $this->_current = $this->_tree;
    }
    
    public function onDocumentEnd()
    {
        if (count($this->_stack) > 0) {
            throw new PHPTAL_ParserException("Not all elements were closed before end of the document (element stack not empty)");
        }
    }

    public function onDocType($doctype)
    {
        $this->pushNode(new PHPTAL_DOMDocumentType($doctype, $this->encoding));
    }

    public function onXmlDecl($decl)
    {
        $this->pushNode(new PHPTAL_DOMXmlDeclaration($decl, $this->encoding));
    }
    
    public function onComment($data)
    {
        if ($this->_stripComments) 
            return;
        $this->pushNode(new PHPTAL_DOMComment($data, $this->encoding));
    }
    
    public function onOther($data)
    {
        $this->pushNode(new PHPTAL_DOMOtherNode($data, $this->encoding));
    }    

    public function onElementStart($element_qname, array $attributes)
    {                
        $this->_xmlns = $this->_xmlns->newElement($attributes);
        
        if (preg_match('/^([^:]+):/',$element_qname,$m))
        {
            $namespace_uri = $this->_xmlns->prefixToNamespaceURI($m[1]);            
            if (false === $namespace_uri) throw new PHPTAL_ParserException("There is no namespace declared for prefix of element <$element_qname>");
        }
        else 
        {
            $namespace_uri = $this->_xmlns->getCurrentDefaultNamespaceURI();
        }
        
        $attrnodes = array();
        foreach($attributes as $qname=>$value) 
        {            
            $local_name = $qname;
            if (preg_match('/^([^:]+):(.+)$/',$qname,$m))
            {
                $local_name = $m[2];
                $attr_namespace_uri = $this->_xmlns->prefixToNamespaceURI($m[1]);
                if (false === $attr_namespace_uri) throw new PHPTAL_ParserException("There is no namespace declared for prefix of attribute $qname of element <$element_qname>");
            }
            else
            {
                $attr_namespace_uri = ''; // default NS. Attributes don't inherit namespace per XMLNS spec
            }

            if ($this->_xmlns->isHandledNamespace($attr_namespace_uri) && !$this->_xmlns->isValidAttributeNS($attr_namespace_uri, $local_name)) {
                throw new PHPTAL_ParserException("Unsupported attribute '$qname'");
            }
      
            $attrnodes[] = new PHPTAL_Php_Attr($qname, $attr_namespace_uri, $value, $this->encoding);
        }
        
        $node = new PHPTAL_DOMElement($element_qname, $namespace_uri, $attrnodes, $this->getXmlnsState());
        $this->pushNode($node);
        $this->_stack[] =  $this->_current;
        $this->_current = $node;
    }
    
    public function onElementData($data)
    {
        $this->pushNode(new PHPTAL_DOMText($data, $this->encoding));
    }

    public function onElementClose($qname)
    {
		if (!$this->_current instanceof PHPTAL_DOMElement) $this->raiseError("Found closing tag for '$qname' where there are no open tags");			
        if ($this->_current->getQualifiedName() != $qname) {
            throw new PHPTAL_ParserException("Tag closure mismatch, expected '".$this->_current->getQualifiedName()."' but was '".$qname."'");
        }
        $this->_current = array_pop($this->_stack);
        if ($this->_current instanceOf PHPTAL_DOMElement)
            $this->_xmlns = $this->_current->getXmlnsState();
    }

    private function pushNode(PHPTAL_DOMNode $node)
    {
        $node->setSource($this->file, $this->line);
        $this->_current->appendChild($node);
    }
    
    public function setSource($file,$line)
    {
        $this->file = $file; $this->line = $line; 
    }
    
    public function setEncoding($encoding)
    {
        $this->encoding = $encoding; 
    }
    
    private $file,$line;
    
    private $encoding;
    private $_tree;    /* PHPTAL_DOMElement */
    private $_stack;   /* array<PHPTAL_DOMNode> */
    private $_current; /* PHPTAL_DOMNode */
    private $_xmlns;   /* PHPTAL_Dom_XmlnsState */
    private $_stripComments = false;
}


