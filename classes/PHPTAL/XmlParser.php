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
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
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
    const ST_CDATA = 7;
    const ST_COMMENT = 8;
    const ST_DOCTYPE = 9;
    const ST_XMLDEC = 15;
    const ST_PREPROC = 10;
    const ST_ATTR_KEY = 11;
    const ST_ATTR_EQ = 12;
    const ST_ATTR_QUOTE = 13;
    const ST_ATTR_VALUE = 14;

    // exceptions error messages
    const ERR_CHARS_BEFORE_DOC_START = 
        "Characters found before the begining of the document !";

    const BOM_STR = "\xef\xbb\xbf";
    
    public function __construct() 
    {
        $this->_file = "<string>";
    }

    public function parseFile($src) 
    {
        $this->_file = $src;
        if (!file_exists($this->_file)) {
            throw new Exception("file $src not found");
        }
        $this->parseString( join("", file($src)) );
    }

    public function parseString($src) 
    {
        // remove BOM (utf8 byte order mark)... 
        if (substr($src,0,3) == self::BOM_STR){
            $src = substr($src, 3);
        }
        
        $this->_line = 1;
        $state = self::ST_ROOT;
        $mark  = 0;
        $len   = strlen($src);

        $quoteStyle = '"';
        $tagname    = "";
        $attribute  = "";
        $attributes = array();

        $this->onDocumentStart();
        for ($i=0; $i<$len; $i++) {        
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
                    else if (!self::isAlpha($c)) {
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
                        $this->onElementStart($tagname, $attributes);
                    }
                    break;

                case self::ST_TAG_CLOSE:
                    if ($c == '>') {
                        $tagname = substr($src, $mark, $i-$mark);
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
                    $this->onElementStart($tagname, $attributes);
                    $this->onElementClose($tagname);
                    break;

                case self::ST_TAG_ATTRIBUTES:
                    if ($c == '>') {
                        $mark = $i+1;   // mark text start
                        $state = self::ST_TEXT;
                        $this->onElementStart($tagname, $attributes);
                    }
                    else if ($c == '/') {
                        $state = self::ST_TAG_SINGLE;
                    }
                    else if (self::isWhiteChar($c)) {
                    }
                    else {
                        $mark = $i; // mark attribute key start
                        $state = self::ST_ATTR_KEY;
                    }
                    break;

                case self::ST_COMMENT:
                    if ($c == '>' and substr($src, $i-2, 2) == '--') {
                        $this->onSpecific(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_CDATA:
                    if ($c == '>' and substr($src, $i-2, 2) == ']]') {
                        $this->onSpecific(substr($src, $mark, $i-$mark+1));
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
                    if ($c == '>') {
                        $this->onDocType(substr($src, $mark, $i-$mark+1));
                        $mark = $i+1; // mark text start
                        $state = self::ST_TEXT;
                    }
                    break;

                case self::ST_PREPROC:
                    if ($c == '>' and $src[$i-1] == '?') {
                        $this->onSpecific(substr($src, $mark, $i-$mark+1));
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
                    if ($c == '"' or $c == '\'') {
                        $quoteStyle = $c;
                        $state = self::ST_ATTR_QUOTE;
                        $mark = $i+1; // mark attribute real value start
                    }
                    break;

                case self::ST_ATTR_QUOTE:
                    if ($c == $quoteStyle) {
                        $attributes[$attribute] = substr($src, $mark, $i-$mark);
                        $state = self::ST_TAG_ATTRIBUTES;
                    }
                    break;
            }
        }
        $this->onDocumentEnd();
    }

    public function getLineNumber()
    {
        return $this->_line;
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
    public abstract function onSpecific($data);
    public abstract function onElementStart($name, $attributes);
    public abstract function onElementClose($name);
    public abstract function onElementData($data);
    public abstract function onDocumentStart();
    public abstract function onDocumentEnd();

    
    protected function raiseError( $errStr )
    {
        $str = "%s error: %s in %s:%d";
        $str = sprintf($str, get_class($this), $errStr, $this->_file, $this->_line);
        throw new Exception($str);
    }
    
    private $_line;
    private $_source;
}


?>
