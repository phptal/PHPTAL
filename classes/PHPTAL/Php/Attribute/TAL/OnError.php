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
// Example:
// 
//      <p tal:on-error="string: Error! This paragraph is buggy!">
//      My name is <span tal:replace="here/SlimShady" />.<br />
//      (My login name is 
//      <b tal:on-error="string: Username is not defined!" 
//         tal:content="user">Unknown</b>)
//      </p>
//  

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_OnError extends PHPTAL_Php_Attribute
{
    const ERR_VAR = '$__err__';
    
    public function start()
    {
        $this->tag->generator->doTry();
        $this->tag->generator->pushCode('ob_start()');
    }
    
    public function end()
    {
        $this->tag->generator->pushCode('ob_end_flush()');        
        $this->tag->generator->doCatch('Exception '.self::ERR_VAR);
        $this->tag->generator->pushCode('$tpl->addError('.self::ERR_VAR.')');
        $this->tag->generator->pushCode('ob_end_clean()');

        $expression = $this->extractEchoType($this->expression);

        $code = $this->tag->generator->evaluateExpression($expression);
        switch ($code) {
            case PHPTAL_TALES_NOTHING_KEYWORD:
                break;

            case PHPTAL_TALES_DEFAULT_KEYWORD:
                $this->tag->generator->pushHtml('<pre class="phptalError"');
                $this->tag->generator->doEcho(self::ERR_VAR);
                $this->tag->generator->pushHtml('</pre>');
                break;
                
            default:
                $this->doEcho($code);
                break;
        }
        $this->tag->generator->doEnd();
    }
}

?>
