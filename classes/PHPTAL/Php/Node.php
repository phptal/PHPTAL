1<?php
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


class PHPTAL_Php_Attr
{
    private $value_escaped, $qualified_name, $namespace_uri;
    
    function __construct($namespace_uri, $qualified_name, $value_escaped)
    {
        $this->value_escaped = $value_escaped;
        $this->qualified_name = $qualified_name;
        $this->namespace_uri = $namespace_uri; 
    }
    
    function getNamespaceURI()
    {
        return $this->namespace_uri;
    }
    
    function getQualifiedName()
    {
        return $this->qualified_name;
    }
    
    function getLocalName()
    {
        $n = explode(':',$this->qualified_name,2);
        return end($n);
    }
    
    function setValue($val)
    {
        $this->value_escaped = htmlspecialchars($val);
    }
    
    function getValueEscaped()
    {
        return $this->value_escaped;
    }
    
    function setPHPCode($code)
    {
        $this->value_escaped = '<?php '.$code.' ?>';
    }
    
    function overwriteWithVariable($phpVariable)
    {
        $this->phpVariable = $phpVariable;
        $this->setPHPCode('echo '.$phpVariable);
    }
    
    private $phpVariable;
    function isOverwritten()
    {
        return $this->phpVariable !== NULL;
    }
    
    function getOverwrittenVariableName()
    {
        return $this->phpVariable;
    }
}

/**
 * Document node abstract class.
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
abstract class PHPTAL_Php_Node
{
    public $node;

    public function __construct(PHPTAL_DOMNode $node)
    {
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

    public abstract function generate(PHPTAL_Php_CodeWriter $gen);
}

/**
 * Node container.
 */
class PHPTAL_Php_Tree extends PHPTAL_Php_Node
{
    public $childNodes;

    private $attributes; // cause error FIXME

    public function __construct(PHPTAL_DOMNode $node) /* must allow documentfragment */
    {
        parent::__construct($node);
        $this->childNodes = array();
        foreach($node->childNodes as $child)
        {
            if ($child instanceOf PHPTAL_DOMElement){
                $gen = new PHPTAL_Php_Element($child);
            }
            else if ($child instanceOf PHPTAL_DOMText){
                $gen = new PHPTAL_Php_Text($child);
            }
            else if ($child instanceOf PHPTAL_DOMDocumentType){
                $gen = new PHPTAL_Php_Doctype($child);
            }
            else if ($child instanceOf PHPTAL_DOMXmlDeclaration){
                $gen = new PHPTAL_Php_XmlDeclaration($child);
            }
            else if ($child instanceOf PHPTAL_DOMSpecific){
                $gen = new PHPTAL_Php_Specific($child);
            }
			else if ($child instanceOf PHPTAL_DOMComment){
				$gen = new PHPTAL_Php_Comment($child);
			}
            else {
                throw new PHPTAL_TemplateException('Unhandled node class '.get_class($child));
            }
            $this->childNodes[] = $gen;
        }
    }

    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        try
        {
        foreach ($this->childNodes as $child){
            $child->generate($codewriter);
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

    protected $qualifiedName, $namespace_uri;
    private $attribute_nodes = array();
    protected $talAttributes = array();
    protected $replaceAttributes = array();
    protected $contentAttributes = array();
    protected $surroundAttributes = array();
    public $headFootDisabled = false;
    public $headPrintCondition = false;
    public $footPrintCondition = false;
    public $hidden = false;

    public function __construct(PHPTAL_DOMElement $node)
    {
        parent::__construct($node);
        $this->qualifiedName = $node->getQualifiedName();
        $this->attribute_nodes = array();
        $this->namespace_uri = $node->getNamespaceURI();
        $this->xmlns = $node->getXmlnsState();   
        
        //TODO: use registered namespaces instead of the raw list
        if (preg_match('/^(tal|metal|phptal|i18n):block$/', $this->qualifiedName, $m)) 
        {
            $this->headFootDisabled = true;
            $prefix = $m[1];
        }
        else
        {
            $prefix = '';
        }          
        
        
        foreach($node->getAttributeNodes() as $attr)
        {
            $qname = $attr->getQualifiedName();
            if ($this->xmlns->isPhpTalAttribute("$prefix:$qname")) 
            {
                $qname = "$prefix:$qname";
            }
            
            $this->attribute_nodes[] = new PHPTAL_Php_Attr($node->getNamespaceURI(), $qname, $attr->getValueEscaped());
        }
                
        $this->separateAttributes();
        $this->orderTalAttributes();
    }

    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($codewriter->isDebugOn()){
            $codewriter->pushCode('$ctx->__line = '.$this->getSourceLine());
            $codewriter->doComment('tag "'.$this->qualifiedName.'" from line '.$this->getSourceLine());
        }


        if (count($this->replaceAttributes) > 0) {
            $this->generateSurroundHead($codewriter);
            foreach ($this->replaceAttributes as $att) {
                $att->start($codewriter);
                $att->end($codewriter);
            }
            $this->generateSurroundFoot($codewriter);
            return;
        }

        $this->generateSurroundHead($codewriter);
        // a surround tag may decide to hide us (tal:define for example)
        if (!$this->hidden){
            $this->generateHead($codewriter);
            $this->generateContent($codewriter);
            $this->generateFoot($codewriter);
        }
        $this->generateSurroundFoot($codewriter);
    }

    private function getOrCreateAttributeNodeByQName($qname)
    {
        foreach($this->attribute_nodes as $attr) if ($attr->getQualifiedName() == $qname) return $attr;    
        
        $attr = new PHPTAL_Php_Attr("FIXME", $qname, NULL);    
        $this->attribute_nodes[] = $attr;
        return $attr;
    }

    public function getAttributeNodes()
    {
        return $this->attribute_nodes;
    }

    /**
     * use PHP code to generate attribute's value. The code must use echo!
     */
    public function setAttributePHPCode($qname, $code)
    {
        $this->getOrCreateAttributeNodeByQName($qname)->setPHPCode($code);
    }

    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($qname)
    {
        return $this->node->hasAttribute($qname);
    }

    public function hasAttributeNS($ns_uri,$localname)
    {
        return NULL !== $this->getAttributeNodeNS($ns_uri, $localname);
    }
    
    public function getAttributeNodeNS($ns_uri,$localname)
    {
        foreach($this->attribute_nodes as $attr) 
        {
            if ($attr->getNamespaceURI() === $ns_uri && $attr->getLocalName() === $localname) {echo "FOUND!\n\n\n";return $attr;}
        }
        return NULL;
    }

    /** Returns HTML-escaped the value of specified PHPTAL attribute. */
    public function getAttributeEscaped($qname)
    {
        return $this->node->getAttributeEscaped($qname);
    }

    /** Returns textual (unescaped) value of specified PHPTAL attribute. */
    public function getAttributeText($name, $encoding)
    {
        return $this->node->getAttributeText($name, $encoding);
    }

    public function isOverwrittenAttribute($qname)
    {
        return $this->getOrCreateAttributeNodeByQName($qname)->isOverwritten();
    }

    public function getOverwrittenAttributeVarName($qname)
    {
        return $this->getOrCreateAttributeNodeByQName($qname)->getOverwrittenVariableName();
    }

    public function overwriteAttributeWithPhpVariable($qname, $phpVariable)
    {
        $this->getOrCreateAttributeNodeByQName($qname)->overwriteWithVariable($phpVariable);
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
        return count($this->attribute_nodes) || $this->hasAttribute('tal:attributes');
    }

    // ~~~~~ Generation methods may be called by some PHPTAL attributes ~~~~~

    public function generateSurroundHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        foreach ($this->surroundAttributes as $att) {
            $att->start($codewriter);
        }
    }

    public function generateHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->headFootDisabled) return;
        if ($this->headPrintCondition) {
            $codewriter->doIf($this->headPrintCondition);
        }

        $codewriter->pushHtml('<'.$this->qualifiedName);
        $this->generateAttributes($codewriter);

        if ($codewriter->getOutputMode() !== PHPTAL::HTML5 && $this->isEmptyNode($codewriter->getOutputMode()))
        {
            $codewriter->pushHtml('/>');
        }
        else {
            $codewriter->pushHtml('>');
        }

        if ($this->headPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateContent(PHPTAL_Php_CodeWriter $codewriter, $realContent=false)
    {
        if ($this->isEmptyNode($codewriter->getOutputMode())){
            return;
        }

        if (!$realContent && count($this->contentAttributes) > 0) {
            foreach ($this->contentAttributes as $att) {
                $att->start($codewriter);
                $att->end($codewriter);
            }
            return;
        }

        parent::generate($codewriter);
    }

    public function generateFoot(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->headFootDisabled)
            return;
        if ($this->isEmptyNode($codewriter->getOutputMode()))
            return;

        if ($this->footPrintCondition) {
            $codewriter->doIf($this->footPrintCondition);
        }

        $codewriter->pushHtml( '</'.$this->qualifiedName.'>' );

        if ($this->footPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateSurroundFoot(PHPTAL_Php_CodeWriter $codewriter)
    {
        for ($i = (count($this->surroundAttributes)-1); $i >= 0; $i--) {
            $this->surroundAttributes[$i]->end($codewriter);
        }
    }

    // ~~~~~ Private members ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private function generateAttributes(PHPTAL_Php_CodeWriter $codewriter)
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
        //  $tag->codewriter->pushCode(
        //  '$__ATT_checked = $somecondition ? \'checked="checked"\' : \'\''
        //  );
        //  $tag->attributes['checked'] = '<?php echo $__ATT_checked ?\>';
        //


        $fullreplaceRx = PHPTAL_Php_Attribute_TAL_Attributes::REGEX_FULL_REPLACE;
        foreach ($this->getAttributeNodes() as $attr) 
        {
            $key = $attr->getQualifiedName();
            $value = $attr->getValueEscaped();
            
            if (preg_match($fullreplaceRx, $value)){
                $codewriter->pushHtml($value);
            }
            else if (strpos($value,'<?php') === 0){
                $codewriter->pushHtml(' '.$key.'="');
                $codewriter->pushRawHtml($value);
                $codewriter->pushHtml('"');
            }
            elseif ($codewriter->getOutputMode() === PHPTAL::HTML5 && PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($key))
            {
                $codewriter->pushHtml(' '.$key);
            }
            else
            {
                $codewriter->pushHtml(' '.$key.'='.$codewriter->quoteAttributeValue($value));
            }
        }
    }

    private function getNodePrefix()
    {
        $result = false;
        if (preg_match('/^(.*?):block$/', $this->qualifiedName, $m)){
            list(,$result) = $m;
        }
        return $result;
    }

    private function isEmptyNode($mode)
    {
        return (($mode === PHPTAL::XHTML || $mode === PHPTAL::HTML5) && PHPTAL_Dom_Defs::getInstance()->isEmptyTag($this->qualifiedName)) ||
               ( $mode === PHPTAL::XML   && !$this->hasContent());
    }

    private function hasContent()
    {
        return count($this->childNodes) > 0 || count($this->contentAttributes) > 0;
    }


    private function separateAttributes()
    {
        $this->talAttributes = array();
        foreach ($this->attribute_nodes as $index => $attr) 
        {
            // remove handled xml namespaces
            if (PHPTAL_Dom_Defs::getInstance()->isHandledXmlNs($attr->getQualifiedName(),$attr->getValueEscaped()))
            {
                unset($this->attribute_nodes[$index]);
            }
            else if ($this->xmlns->isPhpTalAttribute($attr->getQualifiedName())) 
            {
                $this->talAttributes[$attr->getQualifiedName()] = $attr->getValueEscaped();
                unset($this->attribute_nodes[$index]);
            }
            else if (PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attr->getQualifiedName())) 
            {
                $attr->setValue($attr->getLocalName());
            }            
        }
    }

    private function orderTalAttributes()
    {
        $talAttributes = array();
        foreach ($this->talAttributes as $key=>$exp)
        {
            $name = $this->xmlns->unAliasAttribute($key);
            $att = PHPTAL_Dom_Defs::getInstance()->getNamespaceAttribute($name);
            if (array_key_exists($att->getPriority(), $talAttributes))
            {      
                throw new PHPTAL_TemplateException(sprintf(self::ERR_ATTRIBUTES_CONFLICT,
                               $this->qualifiedName,
                               $this->getSourceLine(),
                               $key,
                               $talAttributes[$att->getPriority()][0]
                               ));
            }
            $talAttributes[$att->getPriority()] = array($key, $att, $exp);
        }
        ksort($talAttributes);

        $this->talHandlers = array();
        foreach ($talAttributes as $prio => $dat)
        {
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
    
    function getQualifiedName()
    {
        return $this->qualifiedName;
    }
}

/**
 * @package phptal.php
 */
class PHPTAL_Php_Comment extends PHPTAL_Php_Node
{
	public function generate(PHPTAL_Php_CodeWriter $codewriter)
	{
		if (!preg_match('/^<!--\s*!/',$this->node->getValueEscaped()))
		{
		    $codewriter->pushRawHtml($this->node->getValueEscaped());
	    }
    }
}

/**
 * Document text data representation.
 * @package phptal.php
 */
class PHPTAL_Php_Text extends PHPTAL_Php_Node
{
    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushString($this->node->getValueEscaped());
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
    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushHtml($this->node->getValueEscaped());
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
    public function __construct(PHPTAL_DOMDocumentType $node)
    {
        parent::__construct($node);
    }

    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setDocType($this);
        $codewriter->doDoctype();
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
    public function __construct(PHPTAL_DOMXmlDeclaration $node)
    {
        parent::__construct($node);
    }

    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setXmlDeclaration($this);
        $codewriter->doXmlDeclaration();
    }
}

?>
