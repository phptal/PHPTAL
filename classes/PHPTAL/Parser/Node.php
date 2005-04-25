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

require_once 'PHPTAL/Parser/Defs.php';
require_once 'PHPTAL/PhpGenerator/CodeWriter.php';
require_once 'PHPTAL/PhpGenerator/Attribute.php';

/**
 * Document node abstract class.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Node
{
    public $line;
    public $parser;
    /** 
     * XMLNS aliases propagated from parent nodes and defined by this node
     * attributes.
     */
    public $xmlns;

    public function __construct(PHPTAL_Parser $parser)
    {//{{{
        $this->parser = $parser;
        $this->line = $parser->getLineNumber();
        $this->xmlns = $parser->getXmlnsState();
    }//}}}

    public function getSourceFile()
    {//{{{
        return $this->parser->getSourceFile();
    }//}}}
}

/**
 * Node container.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_NodeTree extends PHPTAL_Node
{
    public $children;

    public function __construct(PHPTAL_Parser $parser)
    {//{{{
        parent::__construct($parser);
        $this->children = array();
    }//}}}
}

/**
 * Document Tag representation.
 *
 * This is the main class used by PHPTAL because TAL is a Template Attribute
 * Language, other Node kinds are (usefull) toys.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_NodeElement extends PHPTAL_NodeTree
{
    public $name;
    public $attributes = array();

    public function __construct(PHPTAL_Parser $parser, $name, $attributes)
    {//{{{
        parent::__construct($parser);
        $this->name = $name;
        $this->attributes = $attributes;
    }//}}}

    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($name)
    {//{{{
        $ns = $this->getNodePrefix();
        foreach ($this->attributes as $key=>$value){
            if ($this->xmlns->unAliasAttribute($key) == $name){
                return true;
            }
            if ($ns && $this->xmlns->unAliasAttribute("$ns:$key") == $name){
                return true;
            }
        }
        return false;
    }//}}}

    /** Returns the value of specified PHPTAL attribute. */
    public function getAttribute($name)
    {//{{{
        $ns = $this->getNodePrefix();
        
        foreach ($this->attributes as $key=>$value){
            if ($this->xmlns->unAliasAttribute($key) == $name){
                return $value;
            }
            if ($ns && $this->xmlns->unAliasAttribute("$ns:$key") == $name){
                return $value;
            }
        }
        return false;
    }//}}}

    /** 
     * Returns true if this element or one of its PHPTAL attributes has some
     * content to print (an empty text node child does not count).
     */
    public function hasRealContent()
    {//{{{
        if (count($this->children) == 0)
            return false;

        if (count($this->children) == 1){
            $child = $this->children[0];
            if ($child instanceOf PHPTAL_NodeText && $child->value == ''){
                return false;
            }
        }

        return true;
    }//}}}

    private function getNodePrefix()
    {//{{{
        $result = false;
        if (preg_match('/^(.*?):block$/', $this->name, $m)){
            list(,$result) = $m;
        }
        return $result;
    }//}}}
    
    private function hasContent()
    {//{{{
        return count($this->children) > 0;
    }//}}}
}

/**
 * Document text data representation.
 */
class PHPTAL_NodeText extends PHPTAL_Node
{
    public $value;

    public function __construct(PHPTAL_Parser $parser, $data)
    {//{{{
        parent::__construct($parser);
        $this->value = $data;
    }//}}}
}

/**
 * Comment, preprocessor, etc... representation.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_NodeSpecific extends PHPTAL_Node
{
    public $value;

    public function __construct(PHPTAL_Parser $parser, $data)
    {//{{{
        parent::__construct($parser);
        $this->value = $data;
    }//}}}
}

/**
 * Document doctype representation.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_NodeDoctype extends PHPTAL_Node
{
    public $value;

    public function __construct(PHPTAL_Parser $parser, $data)
    {//{{{
        parent::__construct($parser);
        $this->value = $data;
    }//}}}
}

/**
 * XML declaration node.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_NodeXmlDeclaration extends PHPTAL_Node
{
    public $value;

    public function __construct(PHPTAL_Parser $parser, $data)
    {//{{{
        parent::__construct($parser);
        $this->value = $data;
    }//}}}
}

?>
