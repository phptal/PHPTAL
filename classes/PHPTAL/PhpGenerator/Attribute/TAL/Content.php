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
//      argument ::= (['text'] | 'structure') expression
//
// Example:
// 
//      <p tal:content="user/name">Fred Farkas</p>
//
//


/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_TAL_Content extends PHPTAL_Attribute
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
            $this->generateDefault();
            return;
        }
        
        $this->doEcho($code);
    }
    
    public function end(){}

    private function generateDefault()
    {
        $this->tag->generateContent(true);
    }
    
    private function generateChainedContent($code)
    {
        $this->tag->generator->noThrow(true);
        $started = false;
        foreach ($code as $exp){
            if ($exp == PHPTAL_TALES_NOTHING_KEYWORD){
                continue;
            }
                
            if ($exp == PHPTAL_TALES_DEFAULT_KEYWORD){
                if ($started){
                    $this->tag->generator->doElse();
                }
                $this->generateDefault();
                break;
            }

            $condition = '$__content__ = '.$exp;
            if (!$started) {
                $started = true;
                $this->tag->generator->doIf($condition);
            }
            else {
                $this->tag->generator->doElseIf($condition);
            }
            $this->doEcho('$__content__');
        }
        
        if ($started){
            $this->tag->generator->doEnd();
        }
        $this->tag->generator->noThrow(false);
    }
}

?>
