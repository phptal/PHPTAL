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


class PHPTAL_Php_Attr
{
    private $value_escaped, $qualified_name, $namespace_uri, $encoding;
    
    function __construct($qualified_name, $namespace_uri,$value_escaped, $encoding)
    {
        $this->value_escaped = $value_escaped;
        $this->qualified_name = $qualified_name;
        $this->namespace_uri = $namespace_uri; 
        $this->encoding = $encoding; 
    }
    
    public function getEncoding()
    {
        return $this->encoding;
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
    function getValue() {return html_entity_decode($this->value_escaped, ENT_QUOTES, $this->encoding);}    
    
    function getValueEscaped()
    {
        return $this->value_escaped;
    }
    
    private function setPHPCode($code)
    {
        $this->value_escaped = '<?php '.$code.' ?>';
    }
    
    function hide() {$this->replacedState = self::HIDDEN;}
    
    function overwriteValueWithVariable($phpVariable)
    {
        $this->replacedState = self::VALUE_REPLACED;
        $this->phpVariable = $phpVariable;
        $this->setPHPCode('echo '.$phpVariable);
    }

    function overwriteFullWithVariable($phpVariable)
    {
        $this->replacedState = self::FULLY_REPLACED;
        $this->phpVariable = $phpVariable;
        $this->setPHPCode('echo '.$phpVariable);
    }
    
    function overwriteValueWithCode($code)
    {
        $this->replacedState = self::VALUE_REPLACED;
        $this->phpVariable = NULL;
        $this->setPHPCode($code);
    }    
    
    private $phpVariable;
    function getOverwrittenVariableName()
    {
        return $this->phpVariable;
    }
    
    const HIDDEN = -1;
    const NOT_REPLACED = 0;
    const VALUE_REPLACED = 1;
    const FULLY_REPLACED = 2;
    private $replacedState = 0;
    
    function getReplacedState()
    {
        return $this->replacedState;
    }
}

/**
 * Document node abstract class.
 */
abstract class PHPTAL_DOMNode
{
    private $value_escaped, $source_file, $source_line, $encoding;

    public function __construct($value_escaped, $encoding)
    {
        $this->value_escaped = $value_escaped;
        $this->encoding = $encoding; 
    }
    
    public function setSource($file,$line)
    {
        $this->source_file = $file;
        $this->source_line = $line;
    }

    public function getSourceFile()
    {
        return $this->source_file;
    }

    public function getSourceLine()
    {
        return $this->source_line;
    }
    
    function getValueEscaped()
    {
        return $this->value_escaped;
    }
    
    function getValue($encoding)
    {
        return html_entity_decode($this->value_escaped,ENT_QUOTES,$this->encoding);
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public abstract function generate(PHPTAL_Php_CodeWriter $gen);
}

/**
 * Document Tag representation.
 *
 * This is the main class used by PHPTAL because TAL is a Template Attribute
 * Language, other Node kinds are (useful) toys.
 *
 */
class PHPTAL_DOMElement extends PHPTAL_DOMNode
{

    protected $qualifiedName, $namespace_uri;
    private $attribute_nodes = array();
    protected $replaceAttributes = array();
    protected $contentAttributes = array();
    protected $surroundAttributes = array();
    public $headFootDisabled = false;
    public $headPrintCondition = false;
    public $footPrintCondition = false;
    public $hidden = false;
    public $childNodes = array();

    public function __construct($qname, $namespace_uri, array $attribute_nodes, PHPTAL_Dom_XmlnsState $xmlns)
    {
        $this->qualifiedName = $qname;
        $this->attribute_nodes = $attribute_nodes;
        $this->namespace_uri = $namespace_uri;
        $this->xmlns = $xmlns;   

        // implements inheritance of element's namespace to tal attributes (<metal: use-macro>)
        foreach($attribute_nodes as $index => $attr)
        {    
            // it'll work only when qname == localname, which is good
            if ($this->xmlns->isValidAttributeNS($namespace_uri,$attr->getQualifiedName())) 
            {
                $this->attribute_nodes[$index] = new PHPTAL_Php_Attr($attr->getQualifiedName(), $namespace_uri, $attr->getValueEscaped(), $attr->getEncoding());
            }
        }
        
        if ($this->xmlns->isHandledNamespace($this->namespace_uri)) 
        {
            $this->headFootDisabled = true;
        }        
        else
        {
            // FIXME: add interpolation here?
            $this->replacePHPAttributes();
        }

        $talAttributes = $this->separateAttributes();
        $this->orderTalAttributes($talAttributes);
    }
    
    public function getXmlnsState()
    {
        return $this->xmlns;
    }
    
    /**
     * support <?php ?> inside attributes
     */
    private function replacePHPAttributes()
    {
        foreach($this->attribute_nodes as $attr)
        {
            $split = preg_split("/<\?(php|=|)(.*?)\?>/",$attr->getValueEscaped(),NULL,PREG_SPLIT_DELIM_CAPTURE);
            if (count($split)==1) continue;
                        
            $new_value = '';            
            for($i=0; $i < count($split); $i += 3)
            {
                if (strlen($split[$i])) $new_value .= 'echo \''.str_replace('\'','\\\'',$split[$i]).'\';';

                if (isset($split[$i+2]))
                {          
                    if ($split[$i+1] === '=') $new_value .= 'echo ';
                    $new_value .= rtrim($split[$i+2],"; \n\r").';';
            }
        }
            $attr->overwriteValueWithCode($new_value);
    }
    }
 
    public function appendChild(PHPTAL_DOMNode $child)
    {
        $this->childNodes[] = $child;
    }

    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        try
        {
            if ($codewriter->isDebugOn())
            {
                $codewriter->pushCode('$ctx->__line = '.$this->getSourceLine());
                $codewriter->doComment('tag "'.$this->qualifiedName.'" from line '.$this->getSourceLine());
            }

            if (count($this->replaceAttributes) > 0) {
                $this->generateSurroundHead($codewriter);
                foreach($this->replaceAttributes as $att) {
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
        catch(PHPTAL_TemplateException $e)
        {
            $e->hintSrcPosition($this->getSourceFile(), $this->getSourceLine());
            throw $e;
        }
    }

    public function getAttributeNodes()
    {
        return $this->attribute_nodes;
    }
    
    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($qname)
    {
        foreach($this->attribute_nodes as $attr) if ($attr->getQualifiedName() == $qname) return true;
        return false;
    }

    public function hasAttributeNS($ns_uri,$localname)
    {
        return NULL !== $this->getAttributeNodeNS($ns_uri, $localname);
    }
    
    public function getAttributeNodeNS($ns_uri,$localname)
    {
        foreach($this->attribute_nodes as $attr) 
        {
            if ($attr->getNamespaceURI() === $ns_uri && $attr->getLocalName() === $localname) return $attr;
        }
        return NULL;
    }

    public function getAttributeNode($qname)
    {
        foreach($this->attribute_nodes as $attr) if ($attr->getQualifiedName() === $qname) return $attr;
        return NULL;
    }
        
    public function getOrCreateAttributeNode($qname)
    {
        if ($attr = $this->getAttributeNode($qname)) return $attr;
        
        $attr = new PHPTAL_Php_Attr($qname, "", NULL, 'UTF-8'); // FIXME: should find namespace and encoding
        $this->attribute_nodes[] = $attr;
        return $attr;
    }
    
    /** Returns textual (unescaped) value of specified element attribute. */
    public function getAttributeNS($namespace_uri, $localname)
    {
        if ($n = $this->getAttributeNodeNS($namespace_uri, $localname))
        {
            return $n->getValue();
        }
        return '';
    }

    /**
     * Returns true if this element or one of its PHPTAL attributes has some
     * content to print (an empty text node child does not count).
     */
    public function hasRealContent()
    {
        if (count($this->contentAttributes) > 0) return true;

        foreach($this->childNodes as $node)
        {
            if (!$child instanceOf PHPTAL_DOMText || $child->getValueEscaped() !== '') return true;
        }
    }

    public function hasRealAttributes()
    {
        if ($this->hasAttributeNS('http://xml.zope.org/namespaces/tal','attributes')) return true;
        foreach($this->attribute_nodes as $attr)
        {
            if ($attr->getReplacedState() !== PHPTAL_Php_Attr::HIDDEN) return true;
        }
        return false;
    }

    // ~~~~~ Generation methods may be called by some PHPTAL attributes ~~~~~

    public function generateSurroundHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        foreach($this->surroundAttributes as $att) {
            $att->start($codewriter);
        }
    }

    public function generateHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->headFootDisabled) return;
        if ($this->headPrintCondition) {
            $codewriter->doIf($this->headPrintCondition);
        }

        $codewriter->pushRawHtml('<'.$this->qualifiedName);
        $this->generateAttributes($codewriter);

        if ($codewriter->getOutputMode() !== PHPTAL::HTML5 && $this->isEmptyNode($codewriter->getOutputMode()))
        {
            $codewriter->pushRawHtml('/>');
        }
        else {
            $codewriter->pushRawHtml('>');
        }

        if ($this->headPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateContent(PHPTAL_Php_CodeWriter $codewriter, $realContent=false)
    {
        if (!$this->isEmptyNode($codewriter->getOutputMode()))
        {
            if ($realContent || !count($this->contentAttributes)) 
            {
                foreach($this->childNodes as $child) 
                {
                    $child->generate($codewriter);
                }
            }
            else foreach($this->contentAttributes as $att) 
            {
                $att->start($codewriter);
                $att->end($codewriter);        
            }
        }
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

        $codewriter->pushRawHtml( '</'.$this->qualifiedName.'>' );

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
        // '$__ATT_' value code, it is very useful for xhtml boolean
        // attributes like selected, checked, etc...
        //
        // example:
        //
        //  $tag->codewriter->pushCode(
        //  '$__ATT_checked = $somecondition ? \'checked="checked"\' : \'\''
        //  );
        //  $tag->attributes['checked'] = '<?php echo $__ATT_checked ?\>';
        //

        foreach($this->getAttributeNodes() as $attr) 
        {
            switch($attr->getReplacedState())
            {
                case PHPTAL_Php_Attr::NOT_REPLACED:
                    $codewriter->pushRawHtml(' '.$attr->getQualifiedName());                    
                    if ($codewriter->getOutputMode() !== PHPTAL::HTML5 || !PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attr->getQualifiedName()))
                    {
                        $codewriter->pushHtml('='.$codewriter->quoteAttributeValue($attr->getValueEscaped()));
                    }
                    break;
                    
                case PHPTAL_Php_Attr::HIDDEN:
                    break;
                    
                case PHPTAL_Php_Attr::FULLY_REPLACED:
                    $codewriter->pushRawHtml($attr->getValueEscaped());
                    break;
                
                case PHPTAL_Php_Attr::VALUE_REPLACED:
                    $codewriter->pushRawHtml(' '.$attr->getQualifiedName().'="');
                    $codewriter->pushRawHtml($attr->getValueEscaped());
                    $codewriter->pushRawHtml('"');
                    break;
            }
        }
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
        $talAttributes = array();
        foreach($this->attribute_nodes as $index => $attr) 
        {
            // remove handled xml namespaces
            if (PHPTAL_Dom_Defs::getInstance()->isHandledXmlNs($attr->getQualifiedName(),$attr->getValueEscaped()))
            {
                unset($this->attribute_nodes[$index]);
            }
            else if ($this->xmlns->isHandledNamespace($attr->getNamespaceURI())) 
            {
                $talAttributes[$attr->getQualifiedName()] = $attr;
                $attr->hide();
            }
            else if (PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attr->getQualifiedName())) 
            {
                $attr->setValue($attr->getLocalName());
            }            
        }
        return $talAttributes;
    }

    private function orderTalAttributes(array $talAttributes)
    {
        $temp = array();
        foreach($talAttributes as $domattr)
        {
            $nsattr = PHPTAL_Dom_Defs::getInstance()->getNamespaceAttribute($domattr->getNamespaceURI(), $domattr->getLocalName());
            if (array_key_exists($nsattr->getPriority(), $temp))
            {      
                throw new PHPTAL_TemplateException(sprintf("Attribute conflict in '%s' at line '%d', '%s' cannot appear with '%s'",
                               $this->qualifiedName,
                               $this->getSourceLine(),
                               $key,
                               $temp[$nsattr->getPriority()][0]
                               ));
            }
            $temp[$nsattr->getPriority()] = array($nsattr, $domattr);
        }
        ksort($temp);

        $this->talHandlers = array();
        foreach($temp as $prio => $dat)
        {
            list($nsattr, $domattr) = $dat;
            $handler = $nsattr->createAttributeHandler($this, $domattr->getValue());
            $this->talHandlers[$prio] = $handler;

            if ($nsattr instanceOf PHPTAL_NamespaceAttributeSurround)
                $this->surroundAttributes[] = $handler;
            else if ($nsattr instanceOf PHPTAL_NamespaceAttributeReplace)
                $this->replaceAttributes[] = $handler;
            else if ($nsattr instanceOf PHPTAL_NamespaceAttributeContent)
                $this->contentAttributes[] = $handler;
            else
                throw new PHPTAL_ParserException("Unknown namespace attribute class ".get_class($nsattr));

        }
    }
    
    function getQualifiedName()
    {
        return $this->qualifiedName;
    }
}

class PHPTAL_DOMComment extends PHPTAL_DOMNode
{
	public function generate(PHPTAL_Php_CodeWriter $codewriter)
	{
		if (!preg_match('/^<!--\s*!/',$this->getValueEscaped()))
		{
		    $codewriter->pushRawHtml($this->getValueEscaped());
	    }
    }
}

/**
 * Document text data representation.
 */
class PHPTAL_DOMText extends PHPTAL_DOMNode
{
    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushHtml($this->getValueEscaped());
    }
}

/**
 * Comment, preprocessor, etc... representation.
 *
 */
class PHPTAL_DOMOtherNode extends PHPTAL_DOMNode
{
    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushHtml($this->getValueEscaped());
    }
}

/**
 * Document doctype representation.
 *
 */
class PHPTAL_DOMDocumentType extends PHPTAL_DOMNode
{
    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setDocType($this->getValueEscaped());
        $codewriter->doDoctype();
    }
}

/**
 * XML declaration node.
 *
 */
class PHPTAL_DOMXmlDeclaration extends PHPTAL_DOMNode
{
    public function generate(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setXmlDeclaration($this->getValueEscaped());
        $codewriter->doXmlDeclaration();
    }
}

?>
