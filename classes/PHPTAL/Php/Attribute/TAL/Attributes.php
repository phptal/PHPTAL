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
    
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        // split attributes using ; delimiter
        $attrs = $codewriter->splitExpression($this->expression);
        foreach ($attrs as $exp) {
            list($attribute, $expression) = $this->parseSetExpression($exp);
            if ($expression) {
                $this->prepareAttribute($codewriter,$attribute, $expression);
            }
        }
    }

    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
    }

    private function prepareAttribute(PHPTAL_Php_CodeWriter $codewriter, $attribute, $expression)
    {
        $code = $this->extractEchoType($expression);
        $code = $codewriter->evaluateExpression($code);

        // if $code is an array then the attribute value is decided by a
        // tales chained expression
        if (is_array($code)) {
            return $this->prepareChainedAttribute($codewriter,$attribute, $code);
        }
       
        // XHTML boolean attribute does not appear when empty or false
        if (PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attribute)) {
            return $this->prepareBooleanAttribute($codewriter,$attribute, $code);
        }
        
        // i18n needs to read replaced value of the attribute, which is not possible if attribute is completely replaced with conditional code
        if ($this->phpelement->hasAttributeNS('http://xml.zope.org/namespaces/i18n','attributes'))
            $this->prepareAttributeUnconditional($codewriter,$attribute,$code);
        else
            $this->prepareAttributeConditional($codewriter,$attribute,$code);
        
    }
   
    /**
     * attribute will be output regardless of its evaluated value. NULL behaves just like "".
     */
    private function prepareAttributeUnconditional(PHPTAL_Php_CodeWriter $codewriter,$attribute,$code)
    {
        // regular attribute which value is the evaluation of $code
        $attkey = self::ATT_VALUE_REPLACE . $this->getVarName($attribute);
        if ($this->_echoType == PHPTAL_Php_Attribute::ECHO_STRUCTURE)
            $value = $code;
        else
            $value = $codewriter->escapeCode($code);
        $codewriter->doSetVar($attkey, $value);
        $this->phpelement->overwriteAttributeWithPhpVariable($attribute, $attkey);        
    }

    /**
     * If evaluated value of attribute is NULL, it will not be output at all.
     */
    private function prepareAttributeConditional(PHPTAL_Php_CodeWriter $codewriter,$attribute,$code)
    {
        // regular attribute which value is the evaluation of $code
        $attkey = self::ATT_FULL_REPLACE . $this->getVarName($attribute);
                 
        $codewriter->doIf("NULL !== ($attkey = ($code))");
        
        if ($this->_echoType !== PHPTAL_Php_Attribute::ECHO_STRUCTURE)
            $codewriter->doSetVar($attkey, "' $attribute=\"'.".$codewriter->escapeCode($attkey).".'\"'");
        else
            $codewriter->doSetVar($attkey, "' $attribute=\"'.$attkey.'\"'");
            
        $codewriter->doElse();
        $codewriter->doSetVar($attkey, "''");
        $codewriter->doEnd();
            
        $this->phpelement->overwriteAttributeWithPhpVariable($attribute, $attkey);
    }

    private $_default_escaped;
    private function prepareChainedAttribute(PHPTAL_Php_CodeWriter $codewriter, $attribute, $chain)
    {
        $this->_default_escaped = false;
        $this->_attribute = $attribute;
        if ($this->phpelement->hasAttribute($attribute)) 
        {
            $this->_default_escaped = $this->phpelement->getAttributeEscaped($attribute);
        }
        $this->_attkey = self::ATT_FULL_REPLACE.$this->getVarName($attribute);
        $executor = new PHPTAL_Php_TalesChainExecutor($codewriter, $chain, $this);
        $this->phpelement->overwriteAttributeWithPhpVariable($attribute, $this->_attkey);
    }

    private function prepareBooleanAttribute(PHPTAL_Php_CodeWriter $codewriter, $attribute, $code)
    {
        $attkey = self::ATT_FULL_REPLACE.$this->getVarName($attribute);
        
        if ($codewriter->getOutputMode() === PHPTAL::HTML5)
        {
            $value  = "' $attribute'";
        }
        else
        {
            $value  = "' $attribute=\"$attribute\"'";
        }
        $codewriter->doIf($code);
        $codewriter->doSetVar($attkey, $value);
        $codewriter->doElse();
        $codewriter->doSetVar($attkey, '\'\'');
        $codewriter->doEnd();
        $this->phpelement->overwriteAttributeWithPhpVariable($attribute, $attkey);
    }

    private function getVarName($attribute)
    {
        return strtr($attribute,':-', '__');
    }

    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $codewriter = $executor->getCodeWriter();
        $executor->doElse();
        $codewriter->doSetVar(
            $this->_attkey, 
            "''"
        );
        $executor->breakChain();
    }

    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $codewriter = $executor->getCodeWriter();
        $executor->doElse();
        $attr_str = ($this->_default_escaped !== false)
            ? ' '.$this->_attribute.'='.$codewriter->quoteAttributeValue($this->_default_escaped)  // default value
            : '';                                 // do not print attribute
        $codewriter->doSetVar($this->_attkey, "'".str_replace("'",'\\\'',$attr_str)."'");
        $executor->breakChain();
    }

    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $exp, $islast)
    {
        $codewriter = $executor->getCodeWriter();
        
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
            $value = $codewriter->escapeCode($this->_attkey);

        $codewriter->doSetVar($this->_attkey, "' $this->_attribute=\"'.$value.'\"'");
    }
}


