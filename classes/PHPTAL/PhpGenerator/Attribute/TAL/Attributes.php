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


/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_TAL_Attributes extends PHPTAL_Attribute
{
    const ATT_FULL_REPLACE = '$__ATT_';
    const ATT_VALUE_REPLACE = '$__att_';
    // this regex is used to determine if an attribute is entirely replaced
    // by a php variable or if only its value is replaced.
    const REGEX_FULL_REPLACE = '/<?php echo \$__ATT_.*? ?>/';
    
    public function start()
    {//{{{
        // retrieve the list of attributes to replace splitting the
        // expression using ; delimiter
        $attrs = $this->tag->generator->splitExpression($this->expression);
        foreach ($attrs as $exp) {
            list($attribute, $expression) = $this->parseExpression($exp);
            if ($expression) {
                $this->prepareAttribute($attribute, $expression);
            }
        }
    }//}}}

    public function end()
    {//{{{
    }//}}}

    private function prepareAttribute($attribute, $expression)
    {//{{{
        $code = $this->tag->generator->evaluateExpression($expression);

        // if $code is an array then the attribute value is decided by a
        // tales chained expression
        if (is_array($code)) {
            return $this->prepareChainedAttribute($attribute, $code);
        }
       
        // XHTML boolean attribute does not appear when empty of false
        if (PHPTAL_Defs::isBooleanAttribute($attribute)) {
            return $this->prepareBooleanAttribute($attribute, $code);
        }
        
        // regular attribute which value is the evaluation of $code
        $attkey = self::ATT_VALUE_REPLACE.$this->getVarName($attribute);
        $value  = $this->tag->generator->escapeCode($code);
        $this->tag->generator->doSetVar($attkey, $value);
        $this->overwriteAttribute($attribute, $attkey);
    }//}}}

    private function prepareChainedAttribute($attribute, $chain)
    {//{{{
        // TODO: chained XHTML boolean attributes

        // default attribute value (from source tag)
        $default = false;
        if (array_key_exists($attribute, $this->tag->attributes)) {
            $default = $this->tag->attributes[$attribute];
        }
        
        // boolean indicating if the chain if/elseif/else started
        $started = false;

        // full attribute replace, the code will decide wether or not the
        // attribute will appear
        $attkey = self::ATT_FULL_REPLACE.$this->getVarName($attribute);
        
        $this->tag->generator->noThrow(true);
        foreach ($chain as $exp){
            // nothing keyword gives an empty attribute value and ends the
            // chain.
            if ($exp == PHPTAL_TALES_NOTHING_KEYWORD){
                if ($started) $this->tag->generator->doElse();
                $this->tag->generator->doSetVar($attkey, "' $attribute=\"\"'");
                break;
            }

            // default keyword gives default value if set or do not print
            // the attribute otherwise and ends the chain.
            if ($exp == PHPTAL_TALES_DEFAULT_KEYWORD){
                if ($started) $this->tag->generator->doElse();
                $code = ($default !== false) 
                    ? "' $attribute=\"$default\"'"  // default value
                    : '\'\'';                       // do not print attribute
                $this->tag->generator->doSetVar($attkey, $code);
                break;
            }

            // regular chain member, we try to evaluate the expression
            // and use its return as attribute value if it gives something
            $condition = "($attkey = $exp) !== null && $attkey !== false";
            if ($started == false){ 
                $started = true;
                $this->tag->generator->doIf($condition);
            }
            else {
                $this->tag->generator->doElseIf($condition);
            }

            $value = $this->tag->generator->escapeCode($attkey);
            $value = "' $attribute=\"'.$value.'\"'";
            $this->tag->generator->doSetVar($attkey, $value);
        }
        $this->tag->generator->doEnd();

        $this->overwriteAttribute($attribute, $attkey);        
        $this->tag->generator->noThrow(false);
    }//}}}

    private function prepareBooleanAttribute($attribute, $code)
    {//{{{
        $attkey = self::ATT_FULL_REPLACE.$this->getVarName($attribute);
        $value  = sprintf('" %s=\"%s\""', $attribute, $attribute);
        $this->tag->generator->doIf($code);
        $this->tag->generator->doSetVar($attkey, $value);
        $this->tag->generator->doElse();
        $this->tag->generator->doSetVar($attkey, '\'\'');
        $this->tag->generator->doEnd();
        $this->overwriteAttribute($attribute, $attkey);
    }//}}}

    private function overwriteAttribute($attribute, $attkey)
    {//{{{
        $this->tag->attributes[$attribute] = '<?php echo '.$attkey.' ?>';
        $this->tag->overwrittenAttributes[$attribute] = $attkey;
    }//}}}
    
    private function getVarName($attribute)
    {//{{{
        $attribute = str_replace(':', '_', $attribute);
        $attribute = str_replace('-', '_', $attribute);
        return $attribute;
    }//}}}

    private function parseExpression($exp)
    {//{{{
        $attributeName = false;
        $expression = false;
        $exp = str_replace(';;', ';', $exp);
        $exp = trim($exp);
        // (attribute)[( expression)]
        if (preg_match('/^([a-z][:\-a-z0-9_]*?)(\s+.*?)?$/ism', $exp, $m)) {
            if (count($m) == 3) {
                list(,$attributeName, $exp) = $m;
                $exp = trim($exp);
            }
            else {
                list(,$attributeName) = $m;
                $exp = false;
            }
        }
        return array($attributeName, $exp);
    }//}}}
}

?>
