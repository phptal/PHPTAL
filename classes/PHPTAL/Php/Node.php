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
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Php_Node
{
    public $node;
    public $generator;

    public function __construct(PHPTAL_Php_CodeWriter $generator, PHPTAL_Dom_Node $node)
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
        return $this->node->getSourceLine();
    }

    public abstract function generate();
}

/**
 * Node container.
 * 
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Tree extends PHPTAL_Php_Node
{
    public $children;
    
    public function __construct(PHPTAL_Php_CodeWriter $gen, $node)
    {
        parent::__construct($gen,$node);
        $this->children = array();
        foreach ($node->getChildren() as $child){
            if ($child instanceOf PHPTAL_Dom_Element){
                $gen = new PHPTAL_Php_Element($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_Dom_Text){
                $gen = new PHPTAL_Php_Text($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_Dom_Doctype){
                $gen = new PHPTAL_Php_Doctype($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_Dom_XmlDeclaration){
                $gen = new PHPTAL_Php_XmlDeclaration($this->generator, $child);
            }
            else if ($child instanceOf PHPTAL_Dom_Specific){
                $gen = new PHPTAL_Php_Specific($this->generator, $child);
            }
			else if ($child instanceOf PHPTAL_Dom_Comment){
				$gen = new PHPTAL_Php_Comment($this->generator, $child);
			}
            else {
                throw new PHPTAL_TemplateException('Unhandled node class '.get_class($child));
            }
            array_push($this->children, $gen);
        }
    }
    
    public function generate()
    {
        try
        {
        foreach ($this->children as $child){
            $child->generate();
        }
    }
        catch(PHPTAL_TemplateException $e)
        {
            $e->hintSrcPosition($this->getSourceFile(), $this->getSourceLine());
            throw $e;
        }
    }
}

/**
 * Document Tag representation.
 *
 * This is the main class used by PHPTAL because TAL is a Template Attribute
 * Language, other Node kinds are (usefull) toys.
 *
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Element extends PHPTAL_Php_Tree
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
    public $headPrintCondition = false;
    public $footPrintCondition = false;
    public $hidden = false;

    public function __construct(PHPTAL_Php_CodeWriter $generator, $node)
    {
        parent::__construct($generator, $node);
        $this->name = $node->getName();
        $this->attributes = $node->attributes;
        $this->xmlns = $node->getXmlnsState();
        $this->prepare();
    }

    private function prepare()
    {
        $this->prepareAttributes();
        $this->separateAttributes();
        $this->orderTalAttributes();
    }

    public function generate()
    {
        if ($this->generator->isDebugOn()){
            $this->generator->pushCode('$ctx->__line = '.$this->getSourceLine());
            $this->generator->doComment('tag "'.$this->name.'" from line '.$this->getSourceLine());
        }
       
        
        if (count($this->replaceAttributes) > 0) {
            $this->generateSurroundHead();
            foreach ($this->replaceAttributes as $att) {
                $att->start();
                $att->end();
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

    /** Returns HTML-escaped the value of specified PHPTAL attribute. */
    public function getAttribute($name)
    {
        return $this->node->getAttribute($name);
    }

    /** Returns textual (unescaped) value of specified PHPTAL attribute. */
    public function getAttributeText($name)
    {
        return $this->node->getAttributeText($name, $this->generator->getEncoding());
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

    public function hasRealAttributes()
    {
        return ((count($this->attributes) - count($this->talAttributes)) > 0) || $this->hasAttribute('tal:attributes');
    }

    // ~~~~~ Generation methods may be called by some PHPTAL attributes ~~~~~
    
    public function generateSurroundHead()
    {
        foreach ($this->surroundAttributes as $att) {
            $att->start();
        }
    }

    public function generateHead()
    {
        if ($this->headFootDisabled) return;
        if ($this->headPrintCondition) {
            $this->generator->doIf($this->headPrintCondition);
        }
        
        $this->generator->pushHtml('<'.$this->name);
        $this->generateAttributes();

        if ($this->isEmptyNode()){
            $this->generator->pushHtml('/>');
        }
        else {
            $this->generator->pushHtml('>');
        }
        
        if ($this->headPrintCondition) {
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
                $att->start();
                $att->end();
            }
            return;
        }
        
        parent::generate();
    }

    public function generateFoot()
    {
        if ($this->headFootDisabled) 
            return;
        if ($this->isEmptyNode())
            return;

        if ($this->footPrintCondition) {
            $this->generator->doIf($this->footPrintCondition);
        }
        
        $this->generator->pushHtml( '</'.$this->name.'>' );

        if ($this->footPrintCondition) {
            $this->generator->doEnd();
        }
    }

    public function generateSurroundFoot()
    {
        for ($i = (count($this->surroundAttributes)-1); $i >= 0; $i--) {
            $this->surroundAttributes[$i]->end();
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
            else if (strpos($value,'<?php') === 0){
                $this->generator->pushHtml(' '.$key.'="');
                $this->generator->pushRawHtml($value);
                $this->generator->pushHtml('"');
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
        return ($this->generator->getOutputMode() == PHPTAL::XHTML && PHPTAL_Dom_Defs::getInstance()->isEmptyTag($this->name)) ||
               ($this->generator->getOutputMode() == PHPTAL::XML   && !$this->hasContent());
    }

    private function hasContent()
    {
        return count($this->children) > 0 || count($this->contentAttributes) > 0;
    }

    private function prepareAttributes()
    {
        //TODO: use registered namespaces instead of the raw list
        if (preg_match('/^(tal|metal|phptal|i18n):block$/', $this->name, $m)) {
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
            if (PHPTAL_Dom_Defs::getInstance()->isHandledXmlNs($key,$value)){
            }
            else if ($this->xmlns->isPhpTalAttribute($key)) {
                $this->talAttributes[$key] = $value;
            }
            else if (PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($key)) {
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
        $attributes = array();
        foreach ($this->talAttributes as $key=>$exp){
            $name = $this->xmlns->unAliasAttribute($key);
            $att = PHPTAL_Dom_Defs::getInstance()->getNamespaceAttribute($name);
            if (array_key_exists($att->getPriority(), $attributes)){
                $err = sprintf(self::ERR_ATTRIBUTES_CONFLICT, 
                               $this->name, 
                               $this->getSourceLine(), 
                               $key, 
                               $attributes[$att->getPriority()][0]
                               );
                throw new PHPTAL_TemplateException($err);
            }
            $attributes[$att->getPriority()] = array($key, $att, $exp);
        }
        ksort($attributes);

        $this->talHandlers = array();
        foreach ($attributes as $prio => $dat){        
            list($key, $att, $exp) = $dat;
            $handler = $att->createAttributeHandler($this, $exp);
            $this->talHandlers[$prio] = $handler;
            
            if ($att instanceOf PHPTAL_NamespaceAttributeSurround)
                $this->surroundAttributes[] = $handler;
            else if ($att instanceOf PHPTAL_NamespaceAttributeReplace)
                $this->replaceAttributes[] = $handler;
            else if ($att instanceOf PHPTAL_NamespaceAttributeContent)
                $this->contentAttributes[] = $handler;
            else 
                throw new PHPTAL_ParserException("Unknown namespace attribute class ".get_class($att));
            
        }
    }
}

/**
 * @package phptal.php
 */
class PHPTAL_Php_Comment extends PHPTAL_Php_Node
{
	public function generate()
	{
		$this->generator->pushRawHtml($this->node->getValue());
	}
}

/**
 * Document text data representation.
 * @package phptal.php
 */
class PHPTAL_Php_Text extends PHPTAL_Php_Node
{
    public function generate()
    {
        $this->generator->pushString($this->node->getValue());
    }
}

/**
 * Comment, preprocessor, etc... representation.
 * 
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Specific extends PHPTAL_Php_Node
{
    public function generate()
    {
        $this->generator->pushHtml($this->node->getValue());
    }
}

/**
 * Document doctype representation.
 * 
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Doctype extends PHPTAL_Php_Node
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
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_XmlDeclaration extends PHPTAL_Php_Node
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
