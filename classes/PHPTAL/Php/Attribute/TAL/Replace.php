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
//      argument ::= (['text'] | 'structure') expression
//
//  Default behaviour : text
//
//      <span tal:replace="template/title">Title</span>
//      <span tal:replace="text template/title">Title</span>
//      <span tal:replace="structure table" />
//      <span tal:replace="nothing">This element is a comment.</span>
//  

require_once PHPTAL_DIR.'PHPTAL/Php/TalesChainExecutor.php';

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_Replace 
extends PHPTAL_Php_Attribute
implements PHPTAL_Php_TalesChainReader
{
    const REPLACE_VAR = '$__replace__';
    
    public function start()
    {
        // tal:replace="" => do nothing and ignore node
        if (trim($this->expression) == ""){
            return;
        }

        $expression = $this->extractEchoType($this->expression);
        $code = $this->tag->generator->evaluateExpression($expression);

        // chained expression
        if (is_array($code)){
            return $this->replaceByChainedExpression($code);
        }

        // nothing do nothing
        if ($code == PHPTAL_TALES_NOTHING_KEYWORD) {
            return;
        }

        // default generate default tag content
        if ($code == PHPTAL_TALES_DEFAULT_KEYWORD) {
            return $this->generateDefault();
        }

        // replace tag with result of expression
        $this->doEcho($code);
    }

    public function end()
    {
    }

    private function replaceByChainedExpression($expArray)
    {
        $executor = new PHPTAL_Php_TalesChainExecutor(
            $this->tag->generator, $expArray, $this
        );
    }

    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->continueChain();
    }

    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->doElse();
        $this->generateDefault();
        $executor->breakChain();
    }

    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $exp, $islast)
    {
        $executor->doIf('!phptal_isempty('.self::REPLACE_VAR.' = '.$exp.')');
        $this->doEcho(self::REPLACE_VAR);
    }

    private function generateDefault()
    {
        $this->tag->generateSurroundHead();
        $this->tag->generateHead();
        $this->tag->generateContent();
        $this->tag->generateFoot();
        $this->tag->generateSurroundFoot();
    }
}

?>
