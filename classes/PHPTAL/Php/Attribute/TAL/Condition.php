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
//      argument ::= expression
//
// Example:
//
//      <p tal:condition="here/copyright"
//         tal:content="here/copyright">(c) 2000</p>
//
//

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';
require_once PHPTAL_DIR.'PHPTAL/Php/TalesChainExecutor.php';

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_Condition 
extends PHPTAL_Php_Attribute
implements PHPTAL_Php_TalesChainReader
{
    private $expressions = array();

    public function start()
    {
        $code = $this->tag->generator->evaluateExpression($this->expression);

        // If it's a chained expression build a new code path
        if (is_array($code)) {
            $this->expressions = array();
            $executor = new PHPTAL_Php_TalesChainExecutor( $this->tag->generator, $code, $this );
            return;
        }

        // Force a falsy condition if the nothing keyword is active
        if ($code == PHPTAL_TALES_NOTHING_KEYWORD) {
            $code = 'false';
        }        

        $this->tag->generator->doIf($code);
    }

    public function end() 
    {
        $this->tag->generator->doEnd();
    }


    public function talesChainPart(PHPTAL_Php_TalesChainExecutor $executor, $exp, $islast)
    {
        // check if the expression is empty
        if ( $exp !== 'false' ) {
            $this->expressions[] = '!phptal_isempty($__content__ = ' . $exp . ')';
        }

        if ( $islast ) {
            // for the last one in the chain build a ORed condition
            $this->tag->generator->doIf( implode(' || ', $this->expressions ) );
            // The executor will always end an if so we output a dummy if
            $executor->doIf('false');
        } 
    }

    public function talesChainNothingKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        // end the chain
        $this->talesChainPart( $executor, 'false', true );
        $executor->breakChain();
    }

    public function talesChainDefaultKeyword(PHPTAL_Php_TalesChainExecutor $executor)
    {
        throw new PHPTAL_ParserException('\'default\' keyword not allowed on condition expressions');
    }

}


