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

/**
 * Simple sax like xml parser for PHPTAL.
 *
 * Because PHP Xml parser libraries tends to fail giving a real xml document 
 * representation (at the time this file was created, it was impossible to 
 * retrieve doctypes, xml declaration, problem with comments and CDATA) this 
 * parser was created and can be manipulated to accept some user errors 
 * like < and < in attribute values or inside text nodes.
 *
 * @package phptal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @see PHPTAL_Dom_Parser
 */
abstract class PHPTAL_XmlParser
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

    // exceptions error messages
    const ERR_CHARS_BEFORE_DOC_START = 
        "Characters found before the beginning of the document!";
    const ERR_EXPECT_VALUE_QUOTE =
        "Unexpected '%s' character, expecting attribute single or double quote";
        
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
        
    public function __construct($input_encoding) 
    {
        $this->input_encoding = $input_encoding; 
        $this->_file = "<string>";
    }

    public function parseFile($src) 
    {
        if (!file_exists($src)) {
            throw new PHPTAL_IOException("file $src not found");
        }
        $this->parseString(file_get_contents($src), $src);
    }

    public function parseString($src, $filename = '<string>') 
    {        
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

        $this->onDocumentStart();
        
        
        $i=0;
        // remove BOM (utf8 byte order mark)... 
        if (substr($src,0,3) == self::BOM_STR){
            $i=3;
        }
        for (; $i<$len; $i++) {        
            $c = $src[$i];

            if ($c == "\n") $this->_line++;

            switch ($state) {
                case self::ST_ROOT:
                    if ($c == '<') {
                        $mark = $i; // mark tag start
                        $state = self::ST_LT;
                    }
                    else if (!self::isWhiteChar($c)) {
                        $this->raiseError(self::ERR_CHARS_BEFORE_DOC_START);
                    }
                    break;

                case self::ST_TEXT:
                    if ($c == '<') {
                        if ($mark != $i) {
                            $this->onElementData(substr($src, $mark, $i-$mark));
                        }
                        $mark = $i;
                        $state = self::ST_LT;
                    }
                    break;

                case self::ST_LT:
                    if ($c == '/') {
                        $mark = $i+1;
                        $state = self::ST_TAG_CLOSE;
                    }
                    else if ($c == '?' and substr($src, $i, 4) == '?xml') {
                        $state = self::ST_XMLDEC;
                    }
                    else if ($c == '?') {
                        $state = self::ST_PREPROC;
                    }
                    else if ($c == '!' and substr($src, $i, 3) == '!--') {
                        $state = self::ST_COMMENT;
                    }
                    else if ($c == '!' and substr($src, $i, 8) == '![CDATA[') {
                        $state = self::ST_CDATA;
                    }
                    else if ($c == '!' and substr($src, $i, 8) == '!DOCTYPE') {
                        $state = self::ST_DOCTYPE;
                    }
                    else if (self::isWhiteChar($c)) {
                        $state = self::ST_TEXT;
                    }
                    else {
                        $mark = $i; // mark node name start
                        $attributes = array();
                        $attribute = "";
                        $state = self::ST_TAG_NAME;
                    }
                    break;

                case self::ST_TAG_NAME:
                    if (self::isWhiteChar($c)) {
                        $tagname = substr($src, $mark, $i-$mark);
                        $state = self::ST_TAG_ATTRIBUTES;
                    }
                    else if ($c == '/') {
                        $tagname = substr($src, $mark, $i-$mark);
                        $state = self::ST_TAG_SINGLE;
                    }
                    else if ($c == '>') {
                        $tagname = substr($src, $mark, $i-$mark);
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                        if (!$this->isValidQName($tagname)) $this->raiseError("Invalid element name '$tagname'");
                        $this->onElementStart($tagname, $attributes);
                    }
                    break;

                case self::ST_TAG_CLOSE:
                    if ($c == '>') {
                        $tagname = rtrim(substr($src, $mark, $i-$mark));
                        $this->onElementClose($tagname);
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
                    if (!$this->isValidQName($tagname)) $this->raiseError("Invalid element name '$tagname'");                    
                    $this->onElementStart($tagname, $attributes);
                    $this->onElementClose($tagname);
                    break;
                
                case self::ST_TAG_BETWEEN_ATTRIBUTE:    
                case self::ST_TAG_ATTRIBUTES:
                    if ($c == '>') {
                        $mark = $i+1;   // mark text start
                        $state = self::ST_TEXT;
                        if (!$this->isValidQName($tagname)) $this->raiseError("Invalid element name '$tagname'");                        
                        $this->onElementStart($tagname, $attributes);
                    }
                    else if ($c == '/') {
                        $state = self::ST_TAG_SINGLE;
                    }
                    else if (self::isWhiteChar($c)) {
                        $state = self::ST_TAG_ATTRIBUTES;
                    }
                    else if ($state === self::ST_TAG_ATTRIBUTES) {
                        $mark = $i; // mark attribute key start
                        $state = self::ST_ATTR_KEY;
                    }
                    else $this->raiseError("Unexpected character '$c' between attributes of <$tagname>");
                    break;

                case self::ST_COMMENT:
                    if ($c == '>' && $i > $mark+4 && substr($src, $i-2, 2) == '--') {
                        
                        if (preg_match('/^-|--|-$/', substr($src, $mark +4, $i-$mark+1 -7)))
                        {
                            $this->raiseError("Ill-formed comment. XML comments are not allowed to contain '--' or start/end with '-': ".substr($src, $mark+4, $i-$mark+1-7));
                        }
                        
                        $this->onComment(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_CDATA:
                    if ($c == '>' and substr($src, $i-2, 2) == ']]') {
                        $this->onOther(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_XMLDEC:
                    if ($c == '?' && substr($src, $i, 2) == '?>') {
                        $this->onXmlDecl(substr($src, $mark, $i-$mark+2));
                        $i++; // skip '>'
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_DOCTYPE:
                    if ($c == '[') {
                        $customDoctype = true;
                    }
                    else if ($customDoctype && $c == '>' && substr($src, $i-1, 2) == ']>'){
                        $customDoctype = false;
                        $this->onDocType(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    else if (!$customDoctype && $c == '>') {
                        $customDoctype = false;
                        $this->onDocType(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_PREPROC:
                    if ($c == '>' and $src[$i-1] == '?') {
                        $this->onOther(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_ATTR_KEY:
                    if (self::isWhiteChar($c)) {
                        $attribute = substr($src, $mark, $i-$mark);
                        $state = self::ST_ATTR_EQ;
                    }
                    else if ($c == '=') {
                        $attribute = substr($src, $mark, $i-$mark);
                        $state = self::ST_ATTR_VALUE;
                    }
                    break;

                case self::ST_ATTR_EQ:
                    if ($c == '=') {
                        $state = self::ST_ATTR_VALUE;
                    }
                    break;

                case self::ST_ATTR_VALUE:
                    if (self::isWhiteChar($c)){
                    }
                    else if ($c == '"' or $c == '\'') {
                        $quoteStyle = $c;
                        $state = self::ST_ATTR_QUOTE;
                        $mark = $i+1; // mark attribute real value start
                    }
                    else {
                        $err = self::ERR_EXPECT_VALUE_QUOTE;
                        $err = sprintf($err, $c);                            
                        $this->raiseError($err);
                    }
                    break;

                case self::ST_ATTR_QUOTE:
                    if ($c == $quoteStyle) {
                        if (!$this->isValidQName($attribute)) $this->raiseError("Invalid attribute name '$attribute'");                        
                        if (isset($attributes[$attribute])) $this->raiseError("Attribute '$attribute' on '$tagname' is defined more than once");
                        $attributes[$attribute] = substr($src, $mark, $i-$mark);
                        $state = self::ST_TAG_BETWEEN_ATTRIBUTE;
                    }
                    break;
            }
        }
        
        if ($state == self::ST_TEXT) // allows text past root node, which is in violation of XML spec
        {
            if ($i > $mark)
            {
                $text = substr($src, $mark, $i-$mark);
                //if (!ctype_space($text)) $this->onElementData($text);
                if (!ctype_space($text)) $this->raiseError("Characters found after end of the root element");
            }
        }
        else
        {
            throw new PHPTAL_ParserException("Finished document in unexpected state: ".self::$state_names[$state]." is not finished");
        }
        
        $this->onDocumentEnd();
    }
    
    private function isValidQName($name)
    {        
        return preg_match('/^([a-z_\x80-\xff]+[a-z0-9._\x80-\xff-]*:)?[a-z_\x80-\xff]+[a-z0-9._\x80-\xff-]*$/i',$name);
    }

    public function getSourceFile()
    {
        return $this->_file;
    }
    
    public function getLineNumber()
    {
        return $this->_line;
    }
    
    private $input_encoding;
    function getEncoding()
    {
        return $this->input_encoding;
    }

    public static function isWhiteChar($c)
    {
        return strpos(" \t\n\r\0", $c) !== false;
    }

    public static function isAlpha($c)
    {
        $char = strtolower($c);
        return ($char >= 'a' && $char <= 'z');
    }

    public abstract function onDocType($doctype);
    public abstract function onXmlDecl($decl);
    public abstract function onOther($data);
    public abstract function onComment($data);
    public abstract function onElementStart($name, array $attributes);
    public abstract function onElementClose($name);
    public abstract function onElementData($data);
    public abstract function onDocumentStart();
    public abstract function onDocumentEnd();
    
    protected function raiseError($errFmt)
    {
        $args = func_get_args();
        $errStr = call_user_func_array('sprintf', $args);
        
        $str = "%s error: %s in %s:%d";
        $str = sprintf($str, get_class($this), $errStr, $this->_file, $this->_line);
        throw new PHPTAL_ParserException($str,$this->_file, $this->_line);
    }
    
    private $_file;
    private $_line;
    private $_source;
}

