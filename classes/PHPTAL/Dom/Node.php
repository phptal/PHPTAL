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
abstract class PHPTAL_Dom_Node
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

/**
 * Node container.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Tree extends PHPTAL_Dom_Node
{
    public function __construct()
    {
        parent::__construct();
        $this->_children = array();
    }

    public function addChild(PHPTAL_Dom_Node $node)
    {
        array_push($this->_children, $node);
    }
    
    public function &getChildren()
    {
        return $this->_children;
    }

    public function clearChildren()
    {
        $this->_children = array();
    }
    
    protected $_children;
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
class PHPTAL_Dom_Element extends PHPTAL_Dom_Tree
{
    private $name;
    public $attributes = array();

    public function __construct($name, $attributes)
    {
        if (!preg_match('/^[a-z_:][a-z0-9._:\x80-\xff-]*$/i',$name)) throw new PHPTAL_ParserException("Invalid element name '$name'");
        parent::__construct();
        $this->name = $name;
        $this->attributes = $attributes;
    }

    public function setXmlnsState(PHPTAL_Dom_XmlnsState $state)
    {
        $this->_xmlns = $state;
        $this->xmlns = $state;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getXmlnsState()
    {
        return $this->_xmlns;
    }

    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($name)
    {
        $ns = $this->getNodePrefix();
        foreach ($this->attributes as $key=>$value){
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
    public function getAttribute($name)
    {
        $ns = $this->getNodePrefix();
        
        foreach ($this->attributes as $key=>$value){
            if ($this->_xmlns->unAliasAttribute($key) == $name){
                return $value;
            }
            if ($ns && $this->_xmlns->unAliasAttribute("$ns:$key") == $name){
                return $value;
            }
        }
        return false;
    }
    
    /** Returns textual (unescaped) value of specified PHPTAL attribute. */
    public function getAttributeText($name, $encoding)
    {
        $v = $this->getAttribute($name); if ($v === false) return false;
        
        return html_entity_decode($v,ENT_QUOTES,$encoding);
    }
    

    /** 
     * Returns true if this element or one of its PHPTAL attributes has some
     * content to print (an empty text node child does not count).
     */
    public function hasRealContent()
    {
        if (count($this->_children) == 0)
            return false;

        if (count($this->_children) == 1){
            $child = $this->_children[0];
            if ($child instanceOf PHPTAL_Dom_Text && $child->getValue() == ''){
                return false;
            }
        }

        return true;
    }

    private function getNodePrefix()
    {
        $result = false;
        if (preg_match('/^(.*?):block$/', $this->name, $m)){
            list(,$result) = $m;
        }
        return $result;
    }
    
    private function hasContent()
    {
        return count($this->_children) > 0;
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
class PHPTAL_Dom_ValueNode extends PHPTAL_Dom_Node
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
class PHPTAL_Dom_Text extends PHPTAL_Dom_ValueNode{}

/**
 * Preprocessor, etc... representation.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Specific extends PHPTAL_Dom_ValueNode {}

/**
 * Comment nodes.
 * @package phptal.dom
 */
class PHPTAL_Dom_Comment extends PHPTAL_Dom_ValueNode {}

/**
 * Document doctype representation.
 * 
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_Doctype extends PHPTAL_Dom_ValueNode {}

/**
 * XML declaration node.
 *
 * @package phptal.dom
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Dom_XmlDeclaration extends PHPTAL_Dom_ValueNode {}

?>
