<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
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
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_Attributes extends PHPTAL_Attribute
{
    public function start()
    {
        $expressions = $this->tag->generator->splitExpression($this->expression);
        foreach ($expressions as $exp) {
            list($attribute, $expression) = $this->parseExpression($exp);
            if ($expression) {
                $this->prepareAttribute( $attribute, $expression );
            }
        }
    }

    private function prepareAttribute( $attribute, $expression )
    {
        $code = $this->tag->generator->evaluateExpression($expression);
        if (is_array($code)) {
            $this->generateChainedAttribute( $attribute, $code );
            return;
        }
        
        if (PHPTAL_Defs::isBooleanAttribute($attribute)) {
            $type = '__att_';
            $this->tag->generator->doIf( $code );
            $code = sprintf('$__att_%s = " %s=\"%s\""', 
                            $this->getVarName($attribute), 
                            $attribute, 
                            $attribute);
            $this->tag->generator->pushCode( $code );
            $this->tag->generator->doElse();
            $code = sprintf('$__att_%s = ""', $this->getVarName($attribute));
            $this->tag->generator->pushCode( $code );
            $this->tag->generator->doEnd();
        }
        else {
            $type = '_ATT_';
            $code = sprintf('$_ATT_%s = %s', $this->getVarName($attribute), $code);
            $this->tag->generator->pushCode( $code );
        }
        $this->tag->attributes[ $attribute ] = 
            '<?= $'.$type.$this->getVarName($attribute).' ?>';
    }

    private function getVarName($attribute)
    {
        $attribute = str_replace(':', '_', $attribute);
        $attribute = str_replace('-', '_', $attribute);
        return $attribute;
    }

    private function generateChainedAttribute( $attribute, $chain )
    {
        if (array_key_exists($attribute, $this->tag->attributes)) {
            $default = $this->tag->attributes[$attribute];
        }
        else {
            $default = false;
        }
        
        $attkey = sprintf('$__att_%s', $this->getVarName($attribute));
        $started = false;
        foreach ($chain as $exp){
            
            if ($exp == PHPTAL_TALES_NOTHING_KEYWORD){
                if ($started) $this->tag->generator->doElse();
                $code = sprintf('%s = \' %s=""\'', $attkey, $attribute);
                $this->tag->generator->pushCode( $code );
                break;
            }

            if ($exp == PHPTAL_TALES_DEFAULT_KEYWORD){
                if ($started) $this->tag->generator->doElse();
                if ($default !== false) {
                    $code = sprintf('%s = \' %s="%s"\'', $attkey, $attribute, $default);
                }
                else {                    
                    $code = sprintf('%s = \'\'', $attkey);
                }
                $this->tag->generator->pushCode( $code );
                $this->tag->attributes[$attribute] = "<?= $attkey ?>";
                break;
            }

            $condition = sprintf('(%s = %s) || %s === ""', $attkey, $exp, $attkey);
            if (!$started){
                $this->tag->generator->doIf($condition);
                $started = true;
            }
            else {
                $this->tag->generator->doElseIf($condition);
            }
            $code = sprintf('%s = \' %s="\'.%s.\'"\'', $attkey, $attribute, $attkey);
            $this->tag->generator->pushCode($code);                
        }
       
        $this->tag->generator->doEnd();
        $this->tag->attributes[$attribute] = "<?= $attkey ?>";
    }
    
    public function end()
    {
    }

    private function parseExpression( $exp )
    {
        $defineVar = false;
        $expression = false;
        
        $exp = str_replace(';;', ';', $exp);
        $exp =  trim($exp);
        if (preg_match('/^([a-z][:\-a-z0-9_]*?)(\s+.*?)?$/ism', $exp, $m)) {
            if (count($m) == 3) {
                list(,$defineVar, $exp) = $m;
                $exp = trim($exp);
            }
            else {
                list(,$defineVar) = $m;
                $exp = false;
            };
        }
        
        return array($defineVar, $exp);
    }
}

?>
