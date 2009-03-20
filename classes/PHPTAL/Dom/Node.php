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
    public function __construct()
    {
    }

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
        $this->qualified_name = $qualified_name; 
        $this->namespace_uri = $namespace_uri; 
        $this->value_escaped = $value_escaped; 
        $this->encoding = $encoding; 
    }
    
    function getQualifiedName() {return $this->qualified_name;}
    function getValueEscaped() {return $this->value_escaped;}
    function getValue() {return html_entity_decode($this->value_escaped, ENT_QUOTES, $this->encoding);}
}

/**
 * Node container.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Tree extends PHPTAL_DOMNode
{
    public function __construct()
    {
        parent::__construct();
        $this->childNodes = array();
    }

    public function appendChild(PHPTAL_DOMNode $node)
    {
        $this->childNodes[] = $node;
    }
    
    public $childNodes;
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
    private $qualifiedName;
    private $attribute_nodes = array();

    public function __construct($qualifiedName, PHPTAL_Dom_XmlnsState $state, array $attribute_nodes)
    {
        if (!preg_match('/^([a-z_.-]*:)?[a-z\x80-\xff][a-z0-9._:\x80-\xff-]*$/i',$qualifiedName)) throw new PHPTAL_ParserException("Invalid element name '$qualifiedName'");
        parent::__construct();
        $this->qualifiedName = $qualifiedName;
        $this->attribute_nodes = $attribute_nodes;
        $this->_xmlns = $state;
    }

    public function getQualifiedName()
    {
        return $this->qualifiedName;
    }

    public function getXmlnsState()
    {
        return $this->_xmlns;
    }

    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($name)
    {
        $ns = $this->getNodePrefix();
        foreach ($this->attribute_nodes as $attr)
        {
            $key = $attr->getQualifiedName();
            
            if ($this->_xmlns->unAliasAttribute($key) == $name){
                return true;
            }
            if ($ns && $this->_xmlns->unAliasAttribute("$ns:$key") == $name){
                return true;
            }
        }
        return false;
    }

    /** Returns HTML-escaped the value of specified PHPTAL attribute. */
    public function getAttributeEscaped($name)
    {
        $ns = $this->getNodePrefix();
        
        foreach ($this->attribute_nodes as $attr)
        {
            $key = $attr->getQualifiedName();
            
            if ($this->_xmlns->unAliasAttribute($key) == $name){
                return $attr->getValueEscaped();
            }
            if ($ns && $this->_xmlns->unAliasAttribute("$ns:$key") == $name){
                return $attr->getValueEscaped();
            }
        }
        return false;
    }
    
    /** Returns textual (unescaped) value of specified PHPTAL attribute. */
    public function getAttributeText($name)
    {
        $ns = $this->getNodePrefix();
        
        foreach ($this->attribute_nodes as $attr)
        {
            $key = $attr->getQualifiedName();
            
            if ($this->_xmlns->unAliasAttribute($key) == $name){
                return $attr->getValue();
            }
            if ($ns && $this->_xmlns->unAliasAttribute("$ns:$key") == $name){
                return $attr->getValue();
            }
        }
    }
    
    public function getAttributeNodes()
    {
        return $this->attribute_nodes;
    }
    
    /** 
     * Returns true if this element or one of its PHPTAL attributes has some
     * content to print (an empty text node child does not count).
     */
    public function hasRealContent()
    {
        if (count($this->childNodes) == 0)
            return false;

        if (count($this->childNodes) == 1){
            $child = $this->childNodes[0];
            if ($child instanceOf PHPTAL_DOMText && $child->getValue() == ''){
                return false;
            }
        }

        return true;
    }

    private function getNodePrefix()
    {
        $result = false;
        if (preg_match('/^(.*?):block$/', $this->qualifiedName, $m)){
            list(,$result) = $m;
        }
        return $result;
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

    public function getValue()
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
