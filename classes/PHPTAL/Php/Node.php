<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.motion-twin.com/
 */

require_once PHPTAL_DIR.'PHPTAL/Dom/Defs.php';

/**
 * For backwards compatibility only. Do not use!
 * @deprecated
 */
interface PHPTAL_Php_Tree
{
}

/**
 * node that represents element's attribute
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMAttr
{
    private $value_escaped, $qualified_name, $namespace_uri, $encoding;

    /**
     * @param string $qualified_name attribute name with prefix
     * @param string $namespace_uri full namespace URI or empty string
     * @param string $value_escaped value with HTML-escaping
     * @param string $encoding character encoding used by the value
     */
    function __construct($qualified_name, $namespace_uri, $value_escaped, $encoding)
    {
        $this->value_escaped = $value_escaped;
        $this->qualified_name = $qualified_name;
        $this->namespace_uri = $namespace_uri;
        $this->encoding = $encoding;
    }

    /**
     * get character encoding used by this attribute.
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * get full namespace URI. "" for default namespace.
     */
    function getNamespaceURI()
    {
        return $this->namespace_uri;
    }

    /**
     * get attribute name including namespace prefix, if any
     */
    function getQualifiedName()
    {
        return $this->qualified_name;
    }

    /**
     * get "foo" of "ns:foo" attribute name
     */
    function getLocalName()
    {
        $n = explode(':',$this->qualified_name,2);
        return end($n);
    }

    /**
     * set plain text as value
     */
    function setValue($val)
    {
        $this->value_escaped = htmlspecialchars($val);
    }
    
    /**
     * get value as plain text
     * 
     * @return string
     */
    function getValue() 
    {
        return html_entity_decode($this->value_escaped, ENT_QUOTES, $this->encoding);
    }

    /**
     * Depends on replaced state. 
     * If value is not replaced, it will return it with HTML escapes.
     * 
     * @see getReplacedState()
     * @see overwriteValueWithVariable()
     */
    function getValueEscaped()
    {
        return $this->value_escaped;
    }

    /**
     * set PHP code as value of this attribute. Code is expected to echo the value.
     */
    private function setPHPCode($code)
    {
        $this->value_escaped = '<?php '.$code.' ?>';
    }

    /**
     * hide this attribute. It won't be generated.
     */
    function hide() 
    {
        $this->replacedState = self::HIDDEN;
    }

    /**
     * generate value of this attribute from variable
     */
    function overwriteValueWithVariable($phpVariable)
    {
        $this->replacedState = self::VALUE_REPLACED;
        $this->phpVariable = $phpVariable;
        $this->setPHPCode('echo '.$phpVariable);
    }

    /**
     * generate complete syntax of this attribute using variable
     */
    function overwriteFullWithVariable($phpVariable)
    {
        $this->replacedState = self::FULLY_REPLACED;
        $this->phpVariable = $phpVariable;
        $this->setPHPCode('echo '.$phpVariable);
    }

    /**
     * use any PHP code to generate this attribute's value
     */
    function overwriteValueWithCode($code)
    {
        $this->replacedState = self::VALUE_REPLACED;
        $this->phpVariable = NULL;
        $this->setPHPCode($code);
    }

    private $phpVariable;
    /**
     * if value was overwritten with variable, get its name
     */
    function getOverwrittenVariableName()
    {
        return $this->phpVariable;
    }

    const HIDDEN = -1;
    const NOT_REPLACED = 0;
    const VALUE_REPLACED = 1;
    const FULLY_REPLACED = 2;
    private $replacedState = 0;

    /**
     * whether getValueEscaped() returns real value or PHP code
     */
    function getReplacedState()
    {
        return $this->replacedState;
    }
}

/**
 * Document node abstract class.
 *
 * @package PHPTAL.dom
 */
abstract class PHPTAL_DOMNode
{
    public $parentNode;

    private $value_escaped, $source_file, $source_line, $encoding;

    public function __construct($value_escaped, $encoding)
    {
        $this->value_escaped = $value_escaped;
        $this->encoding = $encoding;
    }

    /**
     * hint where this node is in source code
     */
    public function setSource($file,$line)
    {
        $this->source_file = $file;
        $this->source_line = $line;
    }

    /**
     * file from which this node comes from
     */
    public function getSourceFile()
    {
        return $this->source_file;
    }

    /**
     * line on which this node was defined
     */
    public function getSourceLine()
    {
        return $this->source_line;
    }

    /**
     * depends on node type. Value will be escaped according to context that node comes from.
     */
    function getValueEscaped()
    {
        return $this->value_escaped;
    }

    /**
     * get value as plain text. Depends on node type.
     */
    function getValue($encoding)
    {
        return html_entity_decode($this->value_escaped,ENT_QUOTES,$this->encoding);
    }

    /**
     * encoding used by vaule of this node.
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * use CodeWriter to compile this element to PHP code
     */
    public abstract function generateCode(PHPTAL_Php_CodeWriter $gen);    
    
    
    /**
     * For backwards compatibility only! Do not use!
     * @deprecated
     */
    public function generate()
    {
        $this->generateCode(self::$_codewriter_bc_hack_);
    }

    /**
     * @deprecated
     */
    static $_codewriter_bc_hack_;
    
    /**
     * For backwards compatibility only
     * @deprecated
     */
    function __get($prop)
    {
        if ($prop === 'children') return $this->childNodes;
        if ($prop === 'node') return $this;
        if ($prop === 'generator') return self::$_codewriter_bc_hack_;
        if ($prop === 'attributes')
        {
            $tmp = array(); foreach($this->getAttributeNodes() as $att) $tmp[$att->getQualifiedName()] = $att->getValueEscaped();
            return $tmp;
        }
        throw new PHPTAL_Exception("There is no property $prop on ".get_class($this));
    }
    
    /**
     * For backwards compatibility only
     * @deprecated
     */
    function getName(){ return $this->getQualifiedName(); }
}

/**
 * Document Tag representation.
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMElement extends PHPTAL_DOMNode implements PHPTAL_Php_Tree
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

    /**
     * @param string $qname         qualified name of the element, e.g. "tal:block"
     * @param string $namespace_uri 
     */
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
                $this->attribute_nodes[$index] = new PHPTAL_DOMAttr($attr->getQualifiedName(), $namespace_uri, $attr->getValueEscaped(), $attr->getEncoding());
            }
        }

        if ($this->xmlns->isHandledNamespace($this->namespace_uri))
        {
            $this->headFootDisabled = true;
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
        $child->parentNode = $this;
        $this->childNodes[] = $child;
    }

    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        // For backwards compatibility only!
        self::$_codewriter_bc_hack_ = $codewriter; // FIXME
        
        try
        {
            $this->replacePHPAttributes();

            if ($codewriter->isDebugOn())
            {
                $codewriter->pushCode('$ctx->_line = '.$this->getSourceLine());
                $codewriter->doComment('tag "'.$this->qualifiedName.'" from line '.$this->getSourceLine());
            }

            if (count($this->replaceAttributes) > 0) {
                $this->generateSurroundHead($codewriter);
                foreach($this->replaceAttributes as $att) {
                    $att->before($codewriter);
                    $att->after($codewriter);
                }
                $this->generateSurroundFoot($codewriter);
                return;
            }

            $this->generateSurroundHead($codewriter);
            // a surround tag may decide to hide us (tal:define for example)
            if (!$this->hidden) {
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

        $attr = new PHPTAL_DOMAttr($qname, "", NULL, 'UTF-8'); // FIXME: should find namespace and encoding
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
            if ($attr->getReplacedState() !== PHPTAL_DOMAttr::HIDDEN) return true;
        }
        return false;
    }

    // ~~~~~ Generation methods may be called by some PHPTAL attributes ~~~~~

    public function generateSurroundHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        foreach($this->surroundAttributes as $att) {
            $att->before($codewriter);
        }
    }

    public function generateHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->headFootDisabled) return;
        if ($this->headPrintCondition) {
            $codewriter->doIf($this->headPrintCondition);
        }

        $codewriter->pushHTML('<'.$this->qualifiedName);
        $this->generateAttributes($codewriter);

        if ($codewriter->getOutputMode() !== PHPTAL::HTML5 && $this->isEmptyNode($codewriter->getOutputMode()))
        {
            $codewriter->pushHTML('/>');
        }
        else {
            $codewriter->pushHTML('>');
        }

        if ($this->headPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateContent(PHPTAL_Php_CodeWriter $codewriter = NULL, $realContent=false)
    {
        // For backwards compatibility only!
        if ($codewriter===NULL) $codewriter = self::$_codewriter_bc_hack_; // FIXME!
        
        if (!$this->isEmptyNode($codewriter->getOutputMode()))
        {
            if ($realContent || !count($this->contentAttributes))
            {
                foreach($this->childNodes as $child)
                {
                    $child->generateCode($codewriter);
                }
            }
            else foreach($this->contentAttributes as $att)
            {
                $att->before($codewriter);
                $att->after($codewriter);
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

        $codewriter->pushHTML( '</'.$this->qualifiedName.'>' );

        if ($this->footPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateSurroundFoot(PHPTAL_Php_CodeWriter $codewriter)
    {
        for ($i = (count($this->surroundAttributes)-1); $i >= 0; $i--) {
            $this->surroundAttributes[$i]->after($codewriter);
        }
    }

    // ~~~~~ Private members ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private function generateAttributes(PHPTAL_Php_CodeWriter $codewriter)
    {
        foreach($this->getAttributeNodes() as $attr)
        {
            switch($attr->getReplacedState())
            {
                case PHPTAL_DOMAttr::NOT_REPLACED:
                    $codewriter->pushHTML(' '.$attr->getQualifiedName());
                    if ($codewriter->getOutputMode() !== PHPTAL::HTML5 || !PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attr->getQualifiedName()))
                    {
                        $codewriter->pushHTML('='.$codewriter->quoteAttributeValue($codewriter->interpolateHTML($attr->getValueEscaped())));
                    }
                    break;

                case PHPTAL_DOMAttr::HIDDEN:
                    break;

                case PHPTAL_DOMAttr::FULLY_REPLACED:
                    $codewriter->pushHTML($attr->getValueEscaped());
                    break;

                case PHPTAL_DOMAttr::VALUE_REPLACED:
                    $codewriter->pushHTML(' '.$attr->getQualifiedName().'="');
                    $codewriter->pushHTML($attr->getValueEscaped());
                    $codewriter->pushHTML('"');
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

    function getNamespaceURI()
    {
        return $this->namespace_uri;
    }

    function getLocalName()
    {
        $n = explode(':',$this->qualifiedName,2);
        return end($n);
    }
}

/**
 * @package PHPTAL.dom
 */
class PHPTAL_DOMComment extends PHPTAL_DOMNode
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        if (!preg_match('/^<!--\s*!/',$this->getValueEscaped()))
        {
            $codewriter->pushHTML($this->getValueEscaped());
        }
    }
}

/**
 * Document text data representation.
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMText extends PHPTAL_DOMNode
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->getValueEscaped() !== '')
        {
            $codewriter->pushHTML($codewriter->interpolateHTML($this->getValueEscaped()));
        }
    }
}

/**
 * processing instructions, including <?php blocks
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMProcessingInstruction extends PHPTAL_DOMNode
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushHTML($codewriter->interpolateHTML($this->getValueEscaped()));
    }
}

/**
 * processing instructions, including <?php blocks
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMCDATASection extends PHPTAL_DOMNode
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $mode = $codewriter->getOutputMode();
        $value = $this->getValueEscaped();
        $inCDATAelement = PHPTAL_Dom_Defs::getInstance()->isCDATAElementInHTML($this->parentNode->getNamespaceURI(), $this->parentNode->getLocalName());

        // in HTML5 must limit it to <script> and <style>
        if ($mode === PHPTAL::HTML5 && $inCDATAelement)
        {
            $codewriter->pushHTML($codewriter->interpolateCDATA(str_replace('</','<\/',$value)));
        }
        elseif (($mode === PHPTAL::XHTML && $inCDATAelement)  // safe for text/html
             || ($mode === PHPTAL::XML && preg_match('/[<>&]/',$value))  // non-useless in XML
             || ($mode !== PHPTAL::HTML5 && preg_match('/<\?|\${structure/',$value)))  // hacks with structure (in X[HT]ML) may need it
        {
            // in text/html "</" is dangerous and the only sensible way to escape is ECMAScript string escapes.
            if ($mode === PHPTAL::XHTML) $value = str_replace('</','<\/',$value);

            $codewriter->pushHTML($codewriter->interpolateCDATA('<![CDATA['.$value.']]>'));
        }
        else
        {
            $codewriter->pushHTML($codewriter->interpolateHTML(htmlspecialchars($value)));
        }
    }
}


/**
 * Document doctype representation.
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMDocumentType extends PHPTAL_DOMNode
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setDocType($this->getValueEscaped());
        $codewriter->doDoctype();
    }
}

/**
 * XML declaration node.
 *
 * @package PHPTAL.dom
 */
class PHPTAL_DOMXmlDeclaration extends PHPTAL_DOMNode
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setXmlDeclaration($this->getValueEscaped());
        $codewriter->doXmlDeclaration();
    }
}

