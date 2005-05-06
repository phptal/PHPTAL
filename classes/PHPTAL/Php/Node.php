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
require_once 'PHPTAL/Php/CodeWriter.php';
require_once 'PHPTAL/Php/Attribute.php';

/**
 * Document node abstract class.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Php_Node
{
    public $node;
    public $generator;

    public function __construct(PHPTAL_Php_CodeWriter $generator, PHPTAL_Node $node)
    {
        $this->generator = $generator;
        $this->node = $node;
    }

    public function getSourceFile()
    {
        return $this->node->getSourceFile();
    }

    public function getSourceLine()
    {
        return $this->node->line;
    }

    public abstract function generate();
}

/**
 * Node container.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_NodeTree extends PHPTAL_Php_Node
{
    public $children;
    
    public function __construct(PHPTAL_Php_CodeWriter $gen, $node)
    {
        parent::__construct($gen,$node);
        $this->children = array();
        foreach ($node->children as $child){
            if ($child instanceOf PHPTAL_NodeElement){
                $gen = new PHPTAL_Php_NodeElement($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_NodeText){
                $gen = new PHPTAL_Php_NodeText($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_NodeDoctype){
                $gen = new PHPTAL_Php_NodeDoctype($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_NodeXmlDeclaration){
                $gen = new PHPTAL_Php_NodeXmlDeclaration($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_NodeSpecific){
                $gen = new PHPTAL_Php_NodeSpecific($this->generator, $child);
            }
            else {
                throw new Exception('Unhandled node class '.get_class($child));
            }
            array_push($this->children, $gen);
        }
    }
    
    public function generate()
    {
        foreach ($this->children as $child){
            $child->generate();
        }
    }
}

/**
 * Document Tag representation.
 *
 * This is the main class used by PHPTAL because TAL is a Template Attribute
 * Language, other Node kinds are (usefull) toys.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_NodeElement extends PHPTAL_Php_NodeTree
{
    const ERR_ATTRIBUTES_CONFLICT =
        "Attribute conflict in '%s' at line '%d', '%s' cannot appear with '%s'";
    
    public $name;
    public $attributes = array();
    public $talAttributes = array();
    public $overwrittenAttributes = array();
    public $replaceAttributes = array();
    public $contentAttributes = array();
    public $surroundAttributes = array();
    public $headFootDisabled = false;
    public $headFootPrintCondition = false;
    public $hidden = false;

    public function __construct(PHPTAL_Php_CodeWriter $generator, $node)
    {
        parent::__construct($generator, $node);
        $this->name = $node->name;
        $this->attributes = $node->attributes;
        $this->xmlns = $node->xmlns;
    }

    public function generate()
    {
        if ($this->generator->isDebugOn()){
            $this->generator->pushCode('$ctx->__line = '.$this->getSourceLine());
        }
        $this->prepareAttributes();
        $this->separateAttributes();
        $this->orderTalAttributes();
       
        if ($this->generator->isDebugOn()){
            $this->generator->doComment("tag '$this->name' from line ".$this->getSourceLine());
        }
        
        if (count($this->replaceAttributes) > 0) {
            $this->generateSurroundHead();
            foreach ($this->replaceAttributes as $att) {
                $att->start( $this );
                $att->end( $this );
            }
            $this->generateSurroundFoot();
            return;
        }

        $this->generateSurroundHead();
        // a surround tag may decide to hide us (tal:define for example)
        if (!$this->hidden){
            $this->generateHead();
            $this->generateContent();
            $this->generateFoot();
        }
        $this->generateSurroundFoot();
    }

    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($name)
    {
        return $this->node->hasAttribute($name);
    }

    /** Returns the value of specified PHPTAL attribute. */
    public function getAttribute($name)
    {
        return $this->node->getAttribute($name);
    }

    public function isOverwrittenAttribute($name)
    {
        return array_key_exists($name, $this->overwrittenAttributes);
    }

    public function getOverwrittenAttributeVarName($name)
    {
        return $this->overwrittenAttributes[$name];
    }
    
    public function overwriteAttributeWithPhpValue($name, $phpVariable)
    {
        $this->attributes[$name] = '<?php echo '.$phpVariable.' ?>';
        $this->overwrittenAttributes[$name] = $phpVariable;
    }

    /** 
     * Returns true if this element or one of its PHPTAL attributes has some
     * content to print (an empty text node child does not count).
     */
    public function hasRealContent()
    {
        return $this->node->hasRealContent() 
            || count($this->contentAttributes) > 0;
    }

    // ~~~~~ Generation methods may be called by some PHPTAL attributes ~~~~~
    
    public function generateSurroundHead()
    {
        foreach ($this->surroundAttributes as $att) {
            $att->start( $this );
        }
    }

    public function generateHead()
    {
        if ($this->headFootDisabled) return;
        if ($this->headFootPrintCondition) {
            $this->generator->doIf($this->headFootPrintCondition);
        }
        
        $this->generator->pushHtml('<'.$this->name);
        $this->generateAttributes();

        if ($this->isEmptyNode()){
            $this->generator->pushHtml('/>');
        }
        else {
            $this->generator->pushHtml('>');
        }
        
        if ($this->headFootPrintCondition) {
            $this->generator->doEnd();
        }
    }
    
    public function generateContent($realContent=false)
    {
        if ($this->isEmptyNode()){
            return;
        }
        
        if (!$realContent && count($this->contentAttributes) > 0) {
            foreach ($this->contentAttributes as $att) {
                $att->start( $this );
                $att->end( $this );
            }
            return;
        }
        
        parent::generate();
    }

    public function generateFoot()
    {
        if ($this->headFootDisabled) return;
        if ($this->isEmptyNode())
            return;

        if ($this->headFootPrintCondition) {
            $this->generator->doIf($this->headFootPrintCondition);
        }
        
        $this->generator->pushHtml( '</'.$this->name.'>' );

        if ($this->headFootPrintCondition) {
            $this->generator->doEnd();
        }
    }

    public function generateSurroundFoot()
    {
        for ($i = (count($this->surroundAttributes)-1); $i>= 0; $i--) {
            $this->surroundAttributes[$i]->end( $this );
        }
    }

    // ~~~~~ Private members ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    
    private function generateAttributes()
    {
        // A phptal attribute can modify any node attribute replacing
        // its value by a <?php echo $somevalue ?\ >.
        //
        // The entire attribute (key="value") can be replaced using the
        // '$__ATT_' value code, it is very usefull for xhtml boolean
        // attributes like selected, checked, etc...
        //
        // example: 
        //  
        //  $tag->generator->pushCode(
        //  '$__ATT_checked = $somecondition ? \'checked="checked"\' : \'\''
        //  );
        //  $tag->attributes['checked'] = '<?php echo $__ATT_checked ?\>';
        // 

        $fullreplaceRx = PHPTAL_Php_Attribute_TAL_Attributes::REGEX_FULL_REPLACE;
        foreach ($this->attributes as $key=>$value) {
            if (preg_match($fullreplaceRx, $value)){
                $this->generator->pushHtml($value);
            }
            else {
                $this->generator->pushHtml(' '.$key.'="'.$value.'"');
            }
        }
    }

    private function getNodePrefix()
    {
        $result = false;
        if (preg_match('/^(.*?):block$/', $this->name, $m)){
            list(,$result) = $m;
        }
        return $result;
    }
    
    private function isEmptyNode()
    {
        return ($this->generator->getOutputMode() == PHPTAL::XHTML && PHPTAL_Defs::isEmptyTag($this->name)) ||
               ($this->generator->getOutputMode() == PHPTAL::XML   && !$this->hasContent());
    }

    private function hasContent()
    {
        return count($this->children) > 0 || count($this->contentAttributes) > 0;
    }

    private function prepareAttributes()
    {
        if (preg_match('/^(.*?):block$/', $this->name, $m)) {
            $this->headFootDisabled = true;
            list(,$ns) = $m;
            $attributes = array();
            foreach ($this->attributes as $key=>$value) {
                if ($this->xmlns->isPhpTalAttribute("$ns:$key")) {
                    $attributes["$ns:$key"] = $value;
                }
                else {
                    $attributes[$key] = $value;
                }
            }
            $this->attributes = $attributes;
        }
    }

    private function separateAttributes()
    {
        $attributes = array();
        $this->talAttributes = array();
        foreach ($this->attributes as $key=>$value) {
            // remove handled xml namespaces
            if (PHPTAL_Defs::isHandledXmlNs($key,$value)){
            }
            else if ($this->xmlns->isPhpTalAttribute($key)) {
                $this->talAttributes[$key] = $value;
            }
            else if (PHPTAL_Defs::isBooleanAttribute($key)) {
                $attributes[$key] = $key;
            }
            else {
                $attributes[$key] = $value;
            }
        }
        $this->attributes = $attributes;
    }

    private function orderTalAttributes()
    {
        $result = array();
        foreach ($this->talAttributes as $key=>$exp) {
            $pos = $this->xmlns->getAttributePriority($key);
            if (array_key_exists($pos, $result)) {
                $err = sprintf(self::ERR_ATTRIBUTES_CONFLICT, 
                               $this->name, 
                               $this->getSourceLine(), 
                               $key, 
                               $result[$pos]->name
                               );
                throw new Exception($err);
            }
            $result[$pos] = PHPTAL_Php_Attribute::createAttribute(
                $this, $this->xmlns->unAliasAttribute($key), $exp 
            );
        }

        ksort($result);
        
        $this->talHandlers = $result;
        foreach ($result as $i=>$handler) {
            $type = PHPTAL_Defs::$DICTIONARY[strtoupper($handler->name)];
            switch ($type) {
                case PHPTAL_Defs::REPLACE:
                    $this->replaceAttributes[] = $handler;
                    break;
                    
                case PHPTAL_Defs::SURROUND:
                    $this->surroundAttributes[] = $handler;
                    break;
                    
                case PHPTAL_Defs::CONTENT:
                    $this->contentAttributes[] = $handler;
                    break;

                default:
                    $err = 'Attribute %s not found in PHPTAL_Defs::$DICTIONARY';
                    $err = sprintf($err, $handler->name);
                    throw new Exception($err);
                    break;
            }
        }
    }
}

/**
 * Document text data representation.
 */
class PHPTAL_Php_NodeText extends PHPTAL_Php_Node
{
    public function generate()
    {
        $this->generator->pushString($this->node->value);
    }
}

/**
 * Comment, preprocessor, etc... representation.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_NodeSpecific extends PHPTAL_Php_Node
{
    public function generate()
    {
        $this->generator->pushHtml($this->node->value);
    }
}

/**
 * Document doctype representation.
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_NodeDoctype extends PHPTAL_Php_Node
{
    public function __construct(PHPTAL_Php_CodeWriter $generator, $node)
    {
        parent::__construct($generator, $node);
        $this->generator->setDocType($this);
    }

    public function generate()
    {;
        $this->generator->doDoctype();
    }
}

/**
 * XML declaration node.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_NodeXmlDeclaration extends PHPTAL_Php_Node
{
    public function __construct(PHPTAL_Php_CodeWriter $gen, $node)
    {
        parent::__construct($gen, $node);
        $this->generator->setXmlDeclaration($this);
    }
    
    public function generate()
    {
        $this->generator->doXmlDeclaration();
    }
}

?>
