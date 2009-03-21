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
require_once PHPTAL_DIR.'PHPTAL/Php/CodeWriter.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

/**
 * Document node abstract class.
 *
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_DOMNode
{
    public function setSource($file, $line)
    {
        $this->_file = $file;
        $this->_line = $line;
    }

    public function getSourceLine()
    {
        return $this->_line;
    }
    
    public function getSourceFile()
    {
        return $this->_file;
    }

    private $_file;
    private $_line;
}

class PHPTAL_DOMAttr
{
    private $qualified_name, $namespace_uri, $value_escaped, $encoding;
    
    function __construct($qualified_name, $namespace_uri, $value_escaped, $encoding)
    {
        assert('$namespace_uri !== "" || false === strpos($qualified_name,":")');
        $this->qualified_name = $qualified_name; 
        $this->namespace_uri = $namespace_uri; 
        $this->value_escaped = $value_escaped; 
        $this->encoding = $encoding; 
    }
    
    function getNamespaceURI() {return $this->namespace_uri;}
    function getQualifiedName() {return $this->qualified_name;}
    function getValueEscaped() {return $this->value_escaped;}
    function getValue() {return html_entity_decode($this->value_escaped, ENT_QUOTES, $this->encoding);}
    function getEncoding() {return $this->encoding;}
    
    function getLocalName()
    {
        $n = explode(':',$this->qualified_name,2);
        return end($n);
    }
}

/**
 * Node container.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Tree extends PHPTAL_DOMNode
{
    public function appendChild(PHPTAL_DOMNode $node)
    {
        $this->childNodes[] = $node;
    }
    
    public $childNodes = array();
}

/**
 * Document Tag representation.
 *
 * This is the main class used by PHPTAL because TAL is a Template Attribute
 * Language, other Node kinds are (usefull) toys.
 *
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_DOMElement extends PHPTAL_Dom_Tree
{
    private $qualifiedName, $namespace_uri;
    private $attribute_nodes = array();

    public function __construct($qualifiedName, $namespace_uri, PHPTAL_Dom_XmlnsState $state, array $attribute_nodes)
    {
        $this->qualifiedName = $qualifiedName;
        $this->attribute_nodes = $attribute_nodes;
        $this->namespace_uri = $namespace_uri; 
        
        assert('$this->namespace_uri !== "" || false === strpos($qualifiedName,":")');
        
        $this->_xmlns = $state;
    }
    
    function getNamespaceURI() {return $this->namespace_uri;}

    public function getQualifiedName()
    {
        return $this->qualifiedName;
    }

    public function getXmlnsState()
    {
        return $this->_xmlns;
    }

    public function getAttributeNodes()
    {
        return $this->attribute_nodes;
    }
    
    private function hasContent()
    {
        return count($this->childNodes) > 0;
    }

    /** 
     * XMLNS aliases propagated from parent nodes and defined by this node
     * attributes.
     */
    protected $_xmlns;
}

/**
 * @package phptal.dom
 */
class PHPTAL_Dom_ValueNode extends PHPTAL_DOMNode
{
    public function __construct($data)
    {
        $this->_value = $data;
    }

    public function getValueEscaped()
    {
        return $this->_value;
    }

    private $_value;
}

/**
 * Document text data representation.
 * @package phptal.dom
 */
class PHPTAL_DOMText extends PHPTAL_Dom_ValueNode{}

/**
 * Preprocessor, etc... representation.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_DOMSpecific extends PHPTAL_Dom_ValueNode {}

/**
 * Comment nodes.
 * @package phptal.dom
 */
class PHPTAL_DOMComment extends PHPTAL_Dom_ValueNode {}

/**
 * Document doctype representation.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_DOMDocumentType extends PHPTAL_Dom_ValueNode {}

/**
 * XML declaration node.
 *
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_DOMXmlDeclaration extends PHPTAL_Dom_ValueNode {}

?>
