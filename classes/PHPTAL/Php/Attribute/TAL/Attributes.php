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

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// TAL Specifications 1.4
//
//       argument             ::= attribute_statement [';' attribute_statement]*
//       attribute_statement  ::= attribute_name expression
//       attribute_name       ::= [namespace ':'] Name
//       namespace            ::= Name
//
// examples:
//
//      <a href="/sample/link.html"
//         tal:attributes="href here/sub/absolute_url">
//      <textarea rows="80" cols="20"
//         tal:attributes="rows request/rows;cols request/cols">
//
// IN PHPTAL: attributes will not work on structured replace.
//

require_once PHPTAL_DIR.'PHPTAL/Php/TalesChainExecutor.php';

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_Attributes 
extends PHPTAL_Php_Attribute
implements PHPTAL_Php_TalesChainReader
{
    const ATT_FULL_REPLACE = '$__ATT_';
    const ATT_VALUE_REPLACE = '$__att_';
    // this regex is used to determine if an attribute is entirely replaced
    // by a php variable or if only its value is replaced.
    const REGEX_FULL_REPLACE = '/<?php echo \$__ATT_.*? ?>/';
    
    public function start()
    {
        // split attributes using ; delimiter
        $attrs = $this->tag->generator->splitExpression($this->expression);
        foreach ($attrs as $exp) {
            list($attribute, $expression) = $this->parseSetExpression($exp);
            if ($expression) {
                $this->prepareAttribute($attribute, $expression);
            }
        }
    }

    public function end()
    {
    }

    private function prepareAttribute($attribute, $expression)
    {
        $code = $this->extractEchoType(trim($expression));
        $code = $this->tag->generator->evaluateExpression($code);

        // if $code is an array then the attribute value is decided by a
        // tales chained expression
        if (is_array($code)) {
            return $this->prepareChainedAttribute2($attribute, $code);
        }
       
        // XHTML boolean attribute does not appear when empty of false
        if (PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attribute)) {
            return $this->prepareBooleanAttribute($attribute, $code);
        }
        
        // i18n needs to read replaced value of the attribute, which is not possible if attribute is completely replaced with conditional code
        if ($this->tag->hasAttribute('i18n:attributes'))
            $this->prepareAttributeUnconditional($attribute,$code);
        else
            $this->prepareAttributeConditional($attribute,$code);
        
    }
   
    /**
     * attribute will be output regardless of its evaluated value. NULL behaves just like "".
     */
    private function prepareAttributeUnconditional($attribute,$code)
    {
        // regular attribute which value is the evaluation of $code
        $attkey = self::ATT_VALUE_REPLACE . $this->getVarName($attribute);
        if ($this->_echoType == PHPTAL_Php_Attribute::ECHO_STRUCTURE)
            $value = $code;
        else
            $value = $this->tag->generator->escapeCode($code);
        $this->tag->generator->doSetVar($attkey, $value);
        $this->tag->overwriteAttributeWithPhpValue($attribute, $attkey);        
    }

    /**
     * If evaluated value of attribute is NULL, it will not be output at all.
     */
    private function prepareAttributeConditional($attribute,$code)
    {
        // regular attribute which value is the evaluation of $code
        $attkey = self::ATT_FULL_REPLACE . $this->getVarName($attribute);
                 
        $this->tag->generator->doIf("NULL !== ($attkey = ($code))");
        
        if ($this->_echoType !== PHPTAL_Php_Attribute::ECHO_STRUCTURE)
            $this->tag->generator->doSetVar($attkey, "' $attribute=\"'.".$this->tag->generator->escapeCode($attkey).".'\"'");
        else
            $this->tag->generator->doSetVar($attkey, "' $attribute=\"'.$attkey.'\"'");
            
        $this->tag->generator->doElse();
        $this->tag->generator->doSetVar($attkey, "''");
        $this->tag->generator->doEnd();
            
        $this->tag->overwriteAttributeWithPhpValue($attribute, $attkey);
    }

    private function prepareChainedAttribute2($attribute, $chain)
    {
        $this->_default = false;
        $this->_attribute = $attribute;
        if (array_key_exists($attribute, $this->tag->attributes)) {
            $this->_default = $this->tag->attributes[$attribute];
        }
        $this->_attkey = self::ATT_FULL_REPLACE.$this->getVarName($attribute);
        $executor = new PHPTAL_Php_TalesChainExecutor($this->tag->generator, $chain, $this);
        $this->tag->overwriteAttributeWithPhpValue($attribute, $this->_attkey);
    }

    private function prepareBooleanAttribute($attribute, $code)
    {
        $attkey = self::ATT_FULL_REPLACE.$this->getVarName($attribute);
        $value  = "' $attribute=\"$attribute\"'";
        $this->tag->generator->doIf($code);
        $this->tag->generator->doSetVar($attkey, $value);
        $this->tag->generator->doElse();
        $this->tag->generator->doSetVar($attkey, '\'\'');
        $this->tag->generator->doEnd();
        $this->tag->overwriteAttributeWithPhpValue($attribute, $attkey);
    }

    private function getVarName($attribute)
    {
        return strtr($attribute,':-', '__');
    }

    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->doElse();
        $this->tag->generator->doSetVar(
            $this->_attkey, 
            "''"
        );
        $executor->breakChain();
    }

    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->doElse();
        $code = ($this->_default !== false)
            ? "' $this->_attribute=\"".str_replace("'",'\\\'',$this->_default)."\"'"  // default value
            : '\'\'';                       // do not print attribute
        $this->tag->generator->doSetVar($this->_attkey, $code);
        $executor->breakChain();
    }

    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $exp, $islast)
    {
        if (!$islast) {
        $condition = "!phptal_isempty($this->_attkey = $exp)";
        }
        else {
            $condition = "NULL !== ($this->_attkey = $exp)";
        }
        
        $executor->doIf($condition);
        if ($this->_echoType == PHPTAL_Php_Attribute::ECHO_STRUCTURE)
            $value = $this->_attkey;
        else
            $value = $this->tag->generator->escapeCode($this->_attkey);

        $this->tag->generator->doSetVar($this->_attkey, "' $this->_attribute=\"'.$value.'\"'");
    }
}

?>
