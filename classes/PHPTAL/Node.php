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

require_once 'PHPTAL/CodeGenerator.php';
require_once 'PHPTAL/Defs.php';
require_once 'PHPTAL/Attribute.php';

/**
 * Document node abstract class.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Node
{
    public $line;
    public $parser;
    public $generator;
    /** 
     * XMLNS aliases propagated from parent nodes and defined by this node
     * attributes.
     */
    public $xmlns;

    public function __construct(PHPTAL_Parser $parser)
    {
        $this->parser = $parser;
        $this->generator = $parser->getGenerator();
        $this->line = $parser->getLineNumber();
        $this->xmlns = $parser->getXmlnsState();
    }

    public abstract function generate();
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
    {
        parent::__construct($parser);
        $this->children = array();
    }

    public function generate()
    {
        foreach ($this->children as $child) 
            $child->generate();
    }
}

/**
 * Document Tag representation.
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_NodeElement extends PHPTAL_NodeTree
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

    public function __construct( PHPTAL_Parser $parser, $name, $attributes )
    {
        parent::__construct($parser);
        $this->name = $name;
        $this->attributes = $attributes;
    }

    public function hasPhpTalAttribute($name)
    {
        $ns = $this->getNodePrefix();
        foreach ($this->attributes as $key=>$value){
            if ($this->xmlns->unAliasAttribute($key) == $name){
                return true;
            }
            if ($ns && $this->xmlns->unAliasAttribute("$ns:$key") == $name){
                return true;
            }
        }
        foreach ($this->talAttributes as $key=>$value){
            if ($this->xmlns->unAliasAttribute($key) == $name){
                return true;
            }
            if ($ns && $this->xmlns->unAliasAttribute("$ns:$key") == $name){
                return true;
            }
        }
        return false;
    }

    public function getPhpTalAttribute($name)
    {
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
    }

    private function getNodePrefix()
    {
        $result = false;
        if (preg_match('/^(.*?):block$/', $this->name, $m)){
            list(,$result) = $m;
        }
        return $result;
    }
    
    public function generate()
    {
        if ($this->generator->isDebugOn()){
            $this->generator->pushCode('$ctx->__line = '.$this->line);
        }
        $this->prepareAttributes();
        $this->separateAttributes();
        $this->orderTalAttributes();
       
        if ($this->generator->isDebugOn()){
            $this->generator->doComment("tag '$this->name' from line $this->line");
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

    public function generateSurroundHead()
    {
        foreach ($this->surroundAttributes as $att) {
            $att->start( $this );
        }
    }

    public function generateSurroundFoot()
    {
        for ($i = (count($this->surroundAttributes)-1); $i>= 0; $i--) {
            $this->surroundAttributes[$i]->end( $this );
        }
    }

    private function isEmptyNode()
    {
        return ($this->generator->getOutputMode() == PHPTAL::XHTML && PHPTAL_Defs::isEmptyTag($this->name)) ||
               ($this->generator->getOutputMode() == PHPTAL::XML   && !$this->hasContent());
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

    private function generateAttributes()
    {
        // A phptal attribute can modify any node attribute replacing
        // its value by a <?php echo $somevalue ?\ >.
        //
        // The entire attribute (key="value") can be replaced using the
        // '$__att_' value code, it is very usefull for xhtml boolean
        // attributes like selected, checked, etc...
        //
        // example: 
        //  
        //  $tag->generator->pushCode(
        //  '$__att_checked = $somecondition ? \'checked="checked"\' : \'\''
        //  );
        //  $tag->attributes['checked'] = '<?php echo $__att_checked ?\>';
        // 

        foreach ($this->attributes as $key=>$value) {
            if (preg_match('/<\?php echo \$__att_.*? \?>/', $value)) { 
                $this->generator->pushHtml($value);
            }
            else {
                $this->generator->pushHtml(' '.$key.'="'.$value.'"');
            }
        }
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

    private function hasContent()
    {
        return count($this->children) > 0 || count($this->contentAttributes) > 0;
    }

    public function hasRealContent()
    {
        if (count($this->children) == 0 && count($this->contentAttributes) == 0)
            return false;

        if (count($this->children) == 1){
            $child = $this->children[0];
            if ($child instanceOf PHPTAL_NodeText && $child->value == ''){
                return false;
            }
        }

        return true;
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
                               $this->line, 
                               $key, 
                               $result[$pos]->name
                               );
                throw new Exception($err);
            }
            $result[$pos] = PHPTAL_Attribute::createAttribute(
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
 * Document text representation.
 * 
 */
class PHPTAL_NodeText extends PHPTAL_Node
{
    public $value;

    public function __construct(PHPTAL_Parser $parser, $data)
    {
        parent::__construct($parser);
        $this->value = $data;
    }
    
    public function generate()
    {
        $this->generator->pushString($this->value);
    }
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
    {
        parent::__construct($parser);
        $this->value = $data;
    }

    public function generate()
    {
        $this->generator->pushHtml($this->value);
    }
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
    {
        parent::__construct($parser);
        $this->value = $data;
        $this->generator->setDocType($this);
    }

    public function generate()
    {
        $code = sprintf('$ctx->setDocType(\'%s\')', 
                        str_replace('\'', '\\\'', $this->value));
        $this->generator->pushCode($code);
    }
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
    {
        parent::__construct($parser);
        $this->value = $data;
        $this->generator->setXmlDeclaration($this);
    }

    public function generate()
    {
        $code = sprintf('$ctx->setXmlDeclaration(\'%s\')',
                        str_replace('\'', '\\\'', $this->value));
        $this->generator->pushCode($code);
    }
}

?>
