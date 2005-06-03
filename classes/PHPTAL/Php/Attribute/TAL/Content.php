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

require_once 'PHPTAL/Php/Attribute.php';

// TAL Specifications 1.4
//
//      argument ::= (['text'] | 'structure') expression
//
// Example:
// 
//      <p tal:content="user/name">Fred Farkas</p>
//
//

require_once 'PHPTAL/Php/TalesChainExecutor.php';

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_Content 
extends PHPTAL_Php_Attribute
implements PHPTAL_Php_TalesChainReader
{
    public function start()
    {
        $expression = $this->extractEchoType($this->expression);
        
        $code = $this->tag->generator->evaluateExpression($expression);

        if (is_array($code)) {
            return $this->generateChainedContent($code);
        }

        if ($code == PHPTAL_TALES_NOTHING_KEYWORD) {
            return;
        }

        if ($code == PHPTAL_TALES_DEFAULT_KEYWORD) {
            return $this->generateDefault();
        }
        
        $this->doEcho($code);
    }
    
    public function end()
    {
    }

    private function generateDefault()
    {
        $this->tag->generateContent(true);
    }
    
    private function generateChainedContent($code)
    {
        $executor = new PHPTAL_Php_TalesChainExecutor($this->tag->generator, $code, $this);
    }

    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $exp)
    {
        $executor->doIf('!phptal_isempty($__content__ = '.$exp.')');
        $this->doEcho('$__content__');
    }
    
    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->breakChain();
    }

    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        $executor->doElse();
        $this->generateDefault();
        $executor->breakChain();
    }
}
?>