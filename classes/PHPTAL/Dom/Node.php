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
 * @link     http://phptal.org/
 */

require_once 'PHPTAL/Dom/Defs.php';
require 'PHPTAL/Dom/Attr.php';

/**
 * Document node abstract class.
 *
 * @package PHPTAL
 * @subpackage dom
 */
abstract class PHPTAL_Dom_Node
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
    public function setSource($file, $line)
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
    function getValue()
    {
        return html_entity_decode($this->value_escaped,ENT_QUOTES, $this->encoding);
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

require 'PHPTAL/Dom/Element.php';

/**
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_Comment extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        if (!preg_match('/^<!--\s*!/', $this->getValueEscaped())) {
            $codewriter->pushHTML($this->getValueEscaped());
        }
    }
}

/**
 * Document text data representation.
 *
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_Text extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->getValueEscaped() !== '') {
            $codewriter->pushHTML($codewriter->interpolateHTML($this->getValueEscaped()));
        }
    }
}

/**
 * processing instructions, including <?php blocks
 *
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_ProcessingInstruction extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->pushHTML($codewriter->interpolateHTML($this->getValueEscaped()));
    }
}

/**
 * processing instructions, including <?php blocks
 *
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_CDATASection extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $mode = $codewriter->getOutputMode();
        $value = $this->getValueEscaped();
        $inCDATAelement = PHPTAL_Dom_Defs::getInstance()->isCDATAElementInHTML($this->parentNode->getNamespaceURI(), $this->parentNode->getLocalName());

        // in HTML5 must limit it to <script> and <style>
        if ($mode === PHPTAL::HTML5 && $inCDATAelement) {
            $codewriter->pushHTML($codewriter->interpolateCDATA(str_replace('</', '<\/', $value)));
        }
        elseif (($mode === PHPTAL::XHTML && $inCDATAelement)  // safe for text/html
             || ($mode === PHPTAL::XML && preg_match('/[<>&]/', $value))  // non-useless in XML
             || ($mode !== PHPTAL::HTML5 && preg_match('/<\?|\${structure/', $value)))  // hacks with structure (in X[HT]ML) may need it
        {
            // in text/html "</" is dangerous and the only sensible way to escape is ECMAScript string escapes.
            if ($mode === PHPTAL::XHTML) $value = str_replace('</', '<\/', $value);

            $codewriter->pushHTML($codewriter->interpolateCDATA('<![CDATA['.$value.']]>'));
        }
        else {
            $codewriter->pushHTML($codewriter->interpolateHTML(htmlspecialchars($value)));
        }
    }
}


/**
 * Document doctype representation.
 *
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_DocumentType extends PHPTAL_Dom_Node
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
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_XmlDeclaration extends PHPTAL_Dom_Node
{
    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        $codewriter->setXmlDeclaration($this->getValueEscaped());
        $codewriter->doXmlDeclaration();
    }
}

