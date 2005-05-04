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

// TAL spec 1.4 for tal:define content
//
// argument       ::= define_scope [';' define_scope]*
// define_scope   ::= (['local'] | 'global') define_var
// define_var     ::= variable_name expression
// variable_name  ::= Name
//
// Note: If you want to include a semi-colon (;) in an expression, it must be escaped by doubling it (;;).*
//
// examples:
// 
//   tal:define="mytitle template/title; tlen python:len(mytitle)"
//   tal:define="global company_name string:Digital Creations, Inc."
//
          

/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_TAL_Define extends PHPTAL_Attribute
{
    private $_buffered = false;
    
    public function start()
    {
        $expressions = $this->tag->generator->splitExpression($this->expression);
        foreach ($expressions as $exp){
            list($defineScope, $defineVar, $expression) = $this->parseExpression($exp);
            if (!$defineVar) {
                continue;
            }
            if ($expression === false && !isset($started)) {
                // first generate and buffer tag content, then put this content
                // in the defineVar
                $this->tag->generator->pushCode( 'ob_start()' );
                $this->tag->generateContent();
                $code = sprintf('$ctx->%s = ob_get_contents()', $defineVar);
                $this->tag->generator->pushCode( $code );
                $this->tag->generator->pushCode( 'ob_end_clean()' );
                $this->_buffered = true;
            }
            else if ($expression !== false) {
                $started = true;
                $code = $this->tag->generator->evaluateExpression($expression);
                if (is_array($code)){
                    $this->tag->generator->noThrow(true);
                    $this->chainedDefine( $defineVar, $code );
                    $this->tag->generator->noThrow(false);
                }
                elseif ($code == PHPTAL_TALES_NOTHING_KEYWORD) {
                    $code = sprintf('$ctx->%s = null', $defineVar);
                    $this->tag->generator->pushCode( $code );
                }
                else {
                    $code = sprintf('$ctx->%s = %s', $defineVar, $code);
                    $this->tag->generator->pushCode( $code );
                }
            }
        }

        // if the content of the tag was not buffered and the tag has something
        // interesting to tell, we echo it
        if ($this->_buffered || !$this->tag->hasRealContent()){
            $this->tag->hidden = true;
        }
    }

    private function chainedDefine( $defineVar, $parts )
    {
        $started = false;
        foreach ($parts as $exp){
            if ($exp == PHPTAL_TALES_NOTHING_KEYWORD){
                if ($started)
                    $this->tag->generator->doElse();
                $php = sprintf('$ctx->%s = null', $defineVar);
                $this->tag->generator->pushCode($php);
                break;
            }
                
            if ($exp == PHPTAL_TALES_DEFAULT_KEYWORD){
                if ($started)
                    $this->tag->generator->doElse();
                $this->tag->generator->pushCode( 'ob_start()' );
                $this->tag->generateContent();
                $code = sprintf('$ctx->%s = ob_get_contents()', $defineVar);
                $this->tag->generator->pushCode( $code );
                $this->tag->generator->pushCode( 'ob_end_clean()' );
                $this->_buffered = true;
                break;
            }

            $condition = sprintf('($ctx->%s = %s) !== null', $defineVar, $exp);
            if (!$started) {
                $this->tag->generator->doIf($condition);
                $started = true;
            }
            else {
                $this->tag->generator->doElseIf($condition);
            }
        }
        if ($started)
            $this->tag->generator->doEnd();
    }
    
    public function end()
    {
    }


    /**
     * Parse the define expression, already splitted in sub parts by ';'.
     */
    public function parseExpression( $exp )
    {
        $defineScope = false;
        $defineVar = false;
        $expression = false;
        
        $exp = str_replace(';;', ';', $exp);
        $exp =  trim($exp);
        if (preg_match('/^(local|global)\s+(.*?)$/ism', $exp, $m)) {
            list(,$defineScope, $exp) = $m;
            $exp = trim($exp);
        }
        if (preg_match('/^([a-z][a-z0-9_]*?)(\s+.*?)?$/ism', $exp, $m)) {
            if (count($m) == 3) {
                list(,$defineVar, $exp) = $m;
                $exp = trim($exp);
            }
            else {
                list(,$defineVar) = $m;
                $exp = false;
            };
        }
        
        return array($defineScope, $defineVar, $exp);
    }
}

?>
