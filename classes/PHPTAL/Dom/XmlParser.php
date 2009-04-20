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

/**
 * Simple sax like xml parser for PHPTAL.
 *
 * Because PHP Xml parser libraries tends to fail giving a real xml document
 * representation (at the time this file was created, it was impossible to
 * retrieve doctypes, xml declaration, problem with comments and CDATA) this
 * parser was created and can be manipulated to accept some user errors
 * like < and < in attribute values or inside text nodes.
 *
 * @package PHPTAL.dom
 * @see PHPTAL_DOM_DocumentBuilder
 */
class PHPTAL_Dom_XmlParser
{
    // available parser states
    const ST_ROOT = 0;
    const ST_TEXT = 1;
    const ST_LT   = 2;
    const ST_TAG_NAME = 3;
    const ST_TAG_CLOSE = 4;
    const ST_TAG_SINGLE = 5;
    const ST_TAG_ATTRIBUTES = 6;
    const ST_TAG_BETWEEN_ATTRIBUTE = 7;
    const ST_CDATA = 8;
    const ST_COMMENT = 9;
    const ST_DOCTYPE = 10;
    const ST_XMLDEC = 11;
    const ST_PREPROC = 12;
    const ST_ATTR_KEY = 13;
    const ST_ATTR_EQ = 14;
    const ST_ATTR_QUOTE = 15;
    const ST_ATTR_VALUE = 16;

    const BOM_STR = "\xef\xbb\xbf";


    static $state_names = array(
      self::ST_ROOT => 'root node',
      self::ST_TEXT => 'text',
      self::ST_LT   => 'start of tag',
      self::ST_TAG_NAME => 'tag name',
      self::ST_TAG_CLOSE => 'closing tag',
      self::ST_TAG_SINGLE => 'self-closing tag',
      self::ST_TAG_ATTRIBUTES => 'tag',
      self::ST_TAG_BETWEEN_ATTRIBUTE => 'tag attributes',
      self::ST_CDATA => 'CDATA',
      self::ST_COMMENT => 'comment',
      self::ST_DOCTYPE => 'doctype',
      self::ST_XMLDEC => 'XML declaration',
      self::ST_PREPROC => 'preprocessor directive',
      self::ST_ATTR_KEY => 'attribute name',
      self::ST_ATTR_EQ => 'attribute value',
      self::ST_ATTR_QUOTE => 'quoted attribute value',
      self::ST_ATTR_VALUE => 'unquoted attribute value',
    );

    private $input_encoding;
    public function __construct($input_encoding)
    {
        $this->input_encoding = $input_encoding;
        $this->_file = "<string>";
    }

    public function parseFile(PHPTAL_DocumentBuilder $builder, $src)
    {
        if (!file_exists($src)) {
            throw new PHPTAL_IOException("file $src not found");
        }
        return $this->parseString($builder, file_get_contents($src), $src);
    }

    public function parseString(PHPTAL_DocumentBuilder $builder, $src, $filename = '<string>')
    {
        try
        {
        $builder->setEncoding($this->input_encoding);
        $this->_file = $filename;

        $this->_line = 1;
        $state = self::ST_ROOT;
        $mark  = 0;
        $len   = strlen($src);

        $quoteStyle = '"';
        $tagname    = "";
        $attribute  = "";
        $attributes = array();

        $customDoctype = false;

        $builder->setSource($this->_file, $this->_line);
        $builder->onDocumentStart();

        $i=0;
        // remove BOM (utf8 byte order mark)...
        if (substr($src, 0, 3) === self::BOM_STR) {
            $i=3;
        }
        for (; $i<$len; $i++) {
            $c = $src[$i];

            if ($c === "\n") $builder->setSource($this->_file, ++$this->_line);

            switch ($state) {
                case self::ST_ROOT:
                    if ($c === '<') {
                        $mark = $i; // mark tag start
                        $state = self::ST_LT;
                    } elseif (!self::isWhiteChar($c)) {
                        $this->raiseError("Characters found before the beginning of the document!");
                    }
                    break;

                case self::ST_TEXT:
                    if ($c === '<') {
                        if ($mark != $i) {
                            $builder->onElementData($this->sanitizeEscapedText(substr($src, $mark, $i-$mark)));
                        }
                        $mark = $i;
                        $state = self::ST_LT;
                    }
                    break;

                case self::ST_LT:
                    if ($c === '/') {
                        $mark = $i+1;
                        $state = self::ST_TAG_CLOSE;
                    } elseif ($c === '?' and strtolower(substr($src, $i, 5)) === '?xml ') {
                        $state = self::ST_XMLDEC;
                    } elseif ($c === '?') {
                        $state = self::ST_PREPROC;
                    } elseif ($c === '!' and substr($src, $i, 3) === '!--') {
                        $state = self::ST_COMMENT;
                    } elseif ($c === '!' and substr($src, $i, 8) === '![CDATA[') {
                        $state = self::ST_CDATA;
                        $mark = $i+8; // past opening tag
                    } elseif ($c === '!' and strtoupper(substr($src, $i, 8)) === '!DOCTYPE') {
                        $state = self::ST_DOCTYPE;
                    } elseif (self::isWhiteChar($c)) {
                        $state = self::ST_TEXT;
                    } else {
                        $mark = $i; // mark node name start
                        $attributes = array();
                        $attribute = "";
                        $state = self::ST_TAG_NAME;
                    }
                    break;

                case self::ST_TAG_NAME:
                    if (self::isWhiteChar($c) || $c === '/' || $c === '>') {
                        $tagname = substr($src, $mark, $i-$mark);
                        if (!$this->isValidQName($tagname)) $this->raiseError("Invalid element name '$tagname'");

                        if ($c === '/') {
                            $state = self::ST_TAG_SINGLE;
                        } elseif ($c === '>') {
                            $mark = $i+1; // mark text start
                            $state = self::ST_TEXT;
                            $builder->onElementStart($tagname, $attributes);
                        } else /* isWhiteChar */ {
                            $state = self::ST_TAG_ATTRIBUTES;
                        }
                    }
                    break;

                case self::ST_TAG_CLOSE:
                    if ($c === '>') {
                        $tagname = rtrim(substr($src, $mark, $i-$mark));
                        $builder->onElementClose($tagname);
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_TAG_SINGLE:
                    if ($c != '>') {
                        // error
                    }
                    $mark = $i+1;   // mark text start
                    $state = self::ST_TEXT;
                    $builder->onElementStart($tagname, $attributes);
                    $builder->onElementClose($tagname);
                    break;

                case self::ST_TAG_BETWEEN_ATTRIBUTE:
                case self::ST_TAG_ATTRIBUTES:
                    if ($c === '>') {
                        $mark = $i+1;   // mark text start
                        $state = self::ST_TEXT;
                        $builder->onElementStart($tagname, $attributes);
                    } elseif ($c === '/') {
                        $state = self::ST_TAG_SINGLE;
                    } elseif (self::isWhiteChar($c)) {
                        $state = self::ST_TAG_ATTRIBUTES;
                    } elseif ($state === self::ST_TAG_ATTRIBUTES) {
                        $mark = $i; // mark attribute key start
                        $state = self::ST_ATTR_KEY;
                    } else $this->raiseError("Unexpected character '$c' between attributes of <$tagname>");
                    break;

                case self::ST_COMMENT:
                    if ($c === '>' && $i > $mark+4 && substr($src, $i-2, 2) === '--') {

                        if (preg_match('/^-|--|-$/', substr($src, $mark +4, $i-$mark+1 -7))) {
                            $this->raiseError("Ill-formed comment. XML comments are not allowed to contain '--' or start/end with '-': ".substr($src, $mark+4, $i-$mark+1-7));
                        }

                        $builder->onComment(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_CDATA:
                    if ($c === '>' and substr($src, $i-2, 2) === ']]') {
                        $builder->onCDATASection(substr($src, $mark, $i-$mark-2));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_XMLDEC:
                    if ($c === '?' && substr($src, $i, 2) === '?>') {
                        $builder->onXmlDecl(substr($src, $mark, $i-$mark+2));
                        $i++; // skip '>'
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_DOCTYPE:
                    if ($c === '[') {
                        $customDoctype = true;
                    } elseif ($customDoctype && $c === '>' && substr($src, $i-1, 2) === ']>') {
                        $customDoctype = false;
                        $builder->onDocType(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    } elseif (!$customDoctype && $c === '>') {
                        $customDoctype = false;
                        $builder->onDocType(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_PREPROC:
                    if ($c === '>' and $src[$i-1] === '?') {
                        $builder->onProcessingInstruction(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_ATTR_KEY:
                    if ($c === '=' || self::isWhiteChar($c)) {
                        $attribute = substr($src, $mark, $i-$mark);
                        if (!$this->isValidQName($attribute)) $this->raiseError("Invalid attribute name '$attribute'");
                        if (isset($attributes[$attribute])) $this->raiseError("Attribute '$attribute' on '$tagname' is defined more than once");

                        if ($c === '=') $state = self::ST_ATTR_VALUE;
                        else /* white char */ $state = self::ST_ATTR_EQ;
                    } elseif ($c === '/' || $c==='>') {
                        $attribute = substr($src, $mark, $i-$mark);
                        $this->raiseError("Could not find value for attribute $attribute before end of tag <$tagname>");
                    }
                    break;

                case self::ST_ATTR_EQ:
                    if ($c === '=') {
                        $state = self::ST_ATTR_VALUE;
                    } elseif (!self::isWhiteChar($c)) $this->raiseError("Unexpected '$c' character, expecting attribute single or double quote");
                    break;

                case self::ST_ATTR_VALUE:
                    if (self::isWhiteChar($c)) {
                    } elseif ($c === '"' or $c === '\'') {
                        $quoteStyle = $c;
                        $state = self::ST_ATTR_QUOTE;
                        $mark = $i+1; // mark attribute real value start
                    } else {
                        $this->raiseError("Unexpected '$c' character, expecting attribute single or double quote");
                    }
                    break;

                case self::ST_ATTR_QUOTE:
                    if ($c === $quoteStyle) {
                        $attributes[$attribute] = $this->sanitizeEscapedText(substr($src, $mark, $i-$mark));
                        $state = self::ST_TAG_BETWEEN_ATTRIBUTE;
                    }
                    break;
            }
        }

        if ($state === self::ST_TEXT) // allows text past root node, which is in violation of XML spec
        {
            if ($i > $mark) {
                $text = substr($src, $mark, $i-$mark);
                if (!ctype_space($text)) $this->raiseError("Characters found after end of the root element");
            }
        } else {
            throw new PHPTAL_ParserException("Finished document in unexpected state: ".self::$state_names[$state]." is not finished");
        }

            $builder->onDocumentEnd();
        }
        catch(PHPTAL_TemplateException $e)
        {
            $e->hintSrcPosition($this->_file, $this->_line);
            throw $e;
        }
        return $builder;
    }

    private function isValidQName($name)
    {
        return preg_match('/^([a-z_\x80-\xff]+[a-z0-9._\x80-\xff-]*:)?[a-z_\x80-\xff]+[a-z0-9._\x80-\xff-]*$/i', $name);
    }

    /**
     * This is where this parser violates XML and refuses to be an annoying bastard.
     * FIXME: check encoding here.
     */
    public function sanitizeEscapedText($str)
    {
        $str = str_replace('&apos;','&#39;', $str); // PHP's html_entity_decode doesn't seem to support that!
        
        /* this is ugly kludge to keep <?php ?> blocks unescaped (even in attributes) */
        $types = ini_get('short_open_tag')?'php|=|':'php';
        $split = preg_split("/(<\?(?:$types).*?\?>)/", $str, null, PREG_SPLIT_DELIM_CAPTURE);

        for($i=0; $i < count($split); $i+=2)
        {
            // escape invalid entities and < >
            $split[$i] = strtr(preg_replace('/&(?!(?:#x?[a-f0-9]+|[a-z][a-z0-9]*);)/i', '&amp;', $split[$i]),array('<'=>'&lt;', '>'=>'&gt;'));
        }
        return implode('', $split);
    }

    public static function _htmlspecialchars($m)
    {
        return htmlspecialchars($m[0]);
    }

    public function getSourceFile()
    {
        return $this->_file;
    }

    public function getLineNumber()
    {
        return $this->_line;
    }

    public static function isWhiteChar($c)
    {
        return strpos(" \t\n\r\0", $c) !== false;
    }

    protected function raiseError($errStr)
    {
        throw new PHPTAL_ParserException($errStr, $this->_file, $this->_line);
    }

    private $_file;
    private $_line;
    private $_source;
}

/**
 * @package PHPTAL.dom
 */
interface PHPTAL_DocumentBuilder
{
    function setEncoding($encoding);
    function setSource($file, $line);

    function onDocType($doctype);
    function onXmlDecl($decl);
    function onCDATASection($data);
    function onProcessingInstruction($data);
    function onComment($data);
    function onElementStart($name, array $attributes);
    function onElementClose($name);
    function onElementData($data);
    function onDocumentStart();
    function onDocumentEnd();
}

