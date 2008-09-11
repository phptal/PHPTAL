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
          

require_once PHPTAL_DIR.'PHPTAL/Php/TalesChainExecutor.php';

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_Define 
extends PHPTAL_Php_Attribute
implements PHPTAL_Php_TalesChainReader
{
    public function start()
    {
        $expressions = $this->tag->generator->splitExpression($this->expression);
        $definesAnyNonGlobalVars = false;

        foreach ($expressions as $exp){
            list($defineScope, $defineVar, $expression) = $this->parseExpression($exp);
            if (!$defineVar) {
                continue;
            }
            
            $this->_defineScope = $defineScope;

            if ($defineScope != 'global') $definesAnyNonGlobalVars = true; // <span tal:define="global foo" /> should be invisible, but <img tal:define="bar baz" /> not

            if ($this->_defineScope != 'global' && !$this->_pushedContext){
                $this->tag->generator->pushContext();
                $this->_pushedContext = true;
            }
            
            $this->_defineVar = $defineVar;
            if ($expression === null) {
                // no expression give, use content of tag as value for newly defined
                // var.
                $this->bufferizeContent();
                continue;
            }
            
            $code = $this->tag->generator->evaluateExpression($expression);
            if (is_array($code)){
                $this->chainedDefine($code);
            }
            elseif ($code == PHPTAL_TALES_NOTHING_KEYWORD) {
                $this->doDefineVarWith('null');
            }
            else {
                $this->doDefineVarWith($code);
            }
        }

        // if the content of the tag was buffered or the tag has nothing to tell, we hide it.
        if ($this->_buffered || (!$definesAnyNonGlobalVars && !$this->tag->hasRealContent() && !$this->tag->hasRealAttributes())){
            $this->tag->hidden = true;
        }
    }

    public function end()
    {
        if ($this->_pushedContext){
            $this->tag->generator->popContext();
        }
    }
    
    private function chainedDefine($parts)
    {
        $executor = new PHPTAL_Php_TalesChainExecutor(
            $this->tag->generator, $parts, $this
        );
    }

    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->doElse();
        $this->doDefineVarWith('null');
        $executor->breakChain();
    }

    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->doElse();
        $this->bufferizeContent();
        $executor->breakChain();
    }

    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $exp, $islast)
    {
        if ($this->_defineScope == 'global'){
            $executor->doIf('($glb->'.$this->_defineVar.' = '.$exp.') !== null');
        }
        else {
            $executor->doIf('($ctx->'.$this->_defineVar.' = '.$exp.') !== null');
        }
    }
    
    /**
     * Parse the define expression, already splitted in sub parts by ';'.
     */
    public function parseExpression($exp)
    {
        $defineScope = false; // (local | global)
        $defineVar   = false; // var to define
        
        // extract defineScope from expression
        $exp = trim($exp);
        if (preg_match('/^(local|global)\s+(.*?)$/ism', $exp, $m)) {
            list(,$defineScope, $exp) = $m;
            $exp = trim($exp);
        }

        // extract varname and expression from remaining of expression
        list($defineVar, $exp) = $this->parseSetExpression($exp);
        if ($exp !== null) $exp = trim($exp);
        return array($defineScope, $defineVar, $exp);
    }

    private function bufferizeContent()
    {
        if (!$this->_buffered){
            $this->tag->generator->pushCode( 'ob_start()' );
            $this->tag->generateContent();
            $this->tag->generator->pushCode('$__tmp_content__ = ob_get_clean()');
            $this->_buffered = true;
        }
        $this->doDefineVarWith('$__tmp_content__');
    }

    private function doDefineVarWith($code)
    {
        if ($this->_defineScope == 'global'){
            $this->tag->generator->doSetVar('$glb->'.$this->_defineVar, $code);
        }
        else {
            $this->tag->generator->doSetVar('$ctx->'.$this->_defineVar, $code);
        }
    }

    private $_buffered = false;
    private $_defineScope = null;
    private $_defineVar = null;
    private $_pushedContext = false;
}

?>
