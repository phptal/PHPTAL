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
 * Tranform php: expressions into their php equivalent.
 *
 * This transformer produce php code for expressions like :
 *
 * - a.b["key"].c().someVar[10].foo()
 * - (a or b) and (c or d)
 * - not myBool
 * - ...
 *
 * The public $prefix variable may be changed to change the context lookup.
 *
 * example:
 * 
 *      $transformer = new PHPTAL_PhpTransformer();
 *      $transformer->prefix = '$ctx->';
 *      $res = $transformer->transform('a.b.c[x]');
 *      $res == '$ctx->a->b->c[$ctx->x]';
 *
 * A brave coder may decide to cleanup the parser, optimize things, and send 
 * me a patch :) He will be welcome. 
 *
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_PhpTransformer
{
    const ST_NONE = 0;
    const ST_STR  = 1;    // 'foo' 
    const ST_ESTR = 2;    // "foo ${x} bar"
    const ST_VAR  = 3;    // abcd
    const ST_NUM  = 4;    // 123.02
    const ST_EVAL = 5;    // ${somevar}
    const ST_MEMBER = 6;  // abcd.x
    const ST_STATIC = 7;  // class::[$]static|const
    const ST_DEFINE = 8;  // @MY_DEFINE
    
    public $prefix = '$';

    private $_state;
    
    public function transform( $str )
    {
        $str = preg_replace( '/\bnot\b/i', '!',   $str );
        $str = preg_replace( '/\bne\b/i',  '!=', $str );
        $str = preg_replace( '/\band\b/i', '&&', $str );
        $str = preg_replace( '/\bor\b/i',  '||', $str );
        $str = preg_replace( '/\blt\b/i',  '<',  $str );
        $str = preg_replace( '/\bgt\b/i',  '>',  $str );
        $str = preg_replace( '/\bge\b/i',  '>=', $str );
        $str = preg_replace( '/\ble\b/i',  '<=', $str );
        $str = preg_replace( '/\beq\b/i',  '==', $str );

        $this->_state = self::ST_NONE;
        $result = "";
        $i = 0;
        $len = strlen($str);
        $inString = false;
        $backslashed = false;
        $instanceOf = false;
        $eval = false;

        for ($i = 0; $i <= $len; $i++) {
            if ($i == $len) $c = "\0";
            else $c = $str[$i];

            switch ($this->_state) {
                case self::ST_NONE:
                    if (self::isAlpha($c)) {
                        $this->_state = self::ST_VAR;
                        $mark = $i;
                    }
                    else if ($c == '"') {
                        $this->_state = self::ST_ESTR;
                        $mark = $i;
                        $inString = true;
                    }
                    else if ($c == '\'') {
                        $this->_state = self::ST_STR;
                        $mark = $i;
                        $inString = true;
                    }
                    else if ($c == ')' || $c == ']' || $c == '}') {
                        $result .= $c;
                        if ($i < $len-1 && $str[$i+1] == '.') {
                            $result .= '->';
                            $this->_state = self::ST_MEMBER;
                            $mark = $i+2;
                            $i+=2;
                        }
                    }
                    else if ($c == '@') { // defines, ignore char
                        $this->_state = self::ST_DEFINE;
                        $mark = $i+1;
                    }
                    else {
                        $result .= $c;
                    }
                    break;
                
                case self::ST_STR:
                    if ($c == '\\') {
                        $backslashed = true;
                    }
                    else if ($backslashed) {
                        $backslashed = false;
                    }
                    else if ($c == '\'') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $inString = false;
                        $this->_state = self::ST_NONE;
                    }
                    break;

                case self::ST_ESTR:
                    if ($c == '\\') {
                        $backslashed = true;
                    }
                    else if ($backslashed) {
                        $backslashed = false;
                    }
                    else if ($c == '"') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $inString = false;
                        $this->_state = self::ST_NONE;
                    }
                    else if ($c == '$' && $i < $len && $str[$i+1] == '{') {
                        $result .= substr( $str, $mark, $i-$mark ) . '{';
                        
                        $sub = 0;
                        for ($j = $i; $j<$len; $j++) {
                            if ($str[$j] == '{') {
                                $sub++;
                            }
                            elseif ($str[$j] == '}' && (--$sub) == 0) {
                                $part = substr( $str, $i+2, $j-$i-2 );
                                $sp = new PHPTAL_PhpTransformer();
                                $sp->prefix = $this->prefix;
                                $result .= $sp->transform( $part );
                                $i = $j;
                                $mark = $i;
                            }
                        }
                    }
                    break;

                case self::ST_VAR:
                    if (self::isVarNameChar($c)) {
                    }
                    else if ($c == '.') {
                        $result .= $this->prefix . substr( $str, $mark, $i-$mark );
                        $result .= '->';
                        $this->_state = self::ST_MEMBER;
                        $mark = $i+1;
                    }
                    else if ($c == ':') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $mark = $i+1;
                        $i++;
                        $this->_state = self::ST_STATIC;
                        break;
                    }
                    else if ($c == '(') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $this->_state = self::ST_NONE;
                    }
                    else if ($c == '[') {
                        $result .= $this->prefix . substr( $str, $mark, $i-$mark+1 );
                        $this->_state = self::ST_NONE;
                    }
                    else {
                        $var = substr( $str, $mark, $i-$mark );
                        if (strtolower($var) == 'true' || strtolower($var) == 'false') {
                            $result .= $var;
                        }
                        else if (strtolower($var) == 'instanceof'){
                            $result .= $var;
                            $instanceOf = true;
                        }
                        else if ($instanceOf){
                            // last was instanceof, this var is a class name
                            $result .= $var;
                            $instanceOf = false;
                        }
                        else {
                            $result .= $this->prefix . $var;
                        }
                        $i--;
                        $this->_state = self::ST_NONE;
                    }
                    break;

                case self::ST_MEMBER:
                    if (self::isVarNameChar($c)) {
                    }
                    else if ($c == '$') {
                        $result .= '{' . $this->prefix;
                        $mark++;
                        $eval = true;
                    }
                    else if ($c == '.') {
                        $result .= substr( $str, $mark, $i-$mark );
                        if ($eval) { $result .='}'; $eval = false; }
                        $result .= '->';
                        $mark = $i+1;
                        $this->_state = self::ST_MEMBER;
                    }
                    else if ($c == ':') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        if ($eval) { $result .='}'; $eval = false; }
                        $this->_state = self::ST_STATIC;
                        break;
                    }
                    else if ($c == '(') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        if ($eval) { $result .='}'; $eval = false; }
                        $this->_state = self::ST_NONE;
                    }
                    else if ($c == '[') {
                        $this->_state = self::ST_NONE;
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        if ($eval) { $result .='}'; $eval = false; }
                    }
                    else {
                        $result .= substr( $str, $mark, $i-$mark );
                        if ($eval) { $result .='}'; $eval = false; }
                        $this->_state = self::ST_NONE;
                        $i--;
                    }   
                    break;

                case self::ST_DEFINE:
                    if (self::isVarNameChar($c)) {
                    }
                    else {
                        $this->_state = self::ST_NONE;
                        $result .= substr( $str, $mark, $i-$mark );
                        $i--;
                    }
                    break;
                    
                case self::ST_STATIC:
                    if (self::isVarNameChar($c)) {
                    }
                    else if ($c == '$') {
                    }
                    else if ($c == '.') {
                        $result .= substr( $str, $mark, $i-$mark );
                        $result .= '->';
                        $mark = $i+1;
                        $this->_state = self::ST_MEMBER;
                    }
                    else if ($c == ':') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $this->_state = self::ST_STATIC;
                        break;
                    }
                    else if ($c == '(') {
                        $result .= substr( $str, $mark, $i-$mark+1 );
                        $this->_state = self::ST_NONE;
                    }
                    else if ($c == '[') {
                        $this->_state = self::ST_NONE;
                        $result .= substr( $str, $mark, $i-$mark+1 );
                    }
                    else {
                        $result .= substr( $str, $mark, $i-$mark );
                        $this->_state = self::ST_NONE;
                        $i--;
                    }   
                    break;

                case self::ST_NUM:
                    if ($c < '0' && $c > '9' && $c != '.') {
                        $result .= substr( $str, $mark, $i-$mark );
                        $this->_state = self::ST_NONE;
                    }
                    break;
            }
        }
        return trim($result);
    }

    private static function isAlpha( $c )
    {
        $c = strtolower($c);
        return $c >= 'a' && $c <= 'z';
    }

    private static function isVarNameChar( $c )
    {
        return self::isAlpha($c) || ($c >= '0' && $c <= '9') || $c == '_';
    }
}

?>
