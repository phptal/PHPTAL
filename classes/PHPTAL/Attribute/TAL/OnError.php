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
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_TAL_OnError extends PHPTAL_Attribute
{
    public function start()
    {
        $this->tag->generator->doTry();
        $this->tag->generator->pushCode('ob_start()');
    }
    
    public function end()
    {
        $this->tag->generator->pushCode('ob_end_flush()');        
        $this->tag->generator->doCatch('Exception $__err__');
        $this->tag->generator->pushCode('$tpl->addError($__err__)');
        $this->tag->generator->pushCode('ob_end_clean()');

        list($echoType, $expression) = $this->parseExpression( $this->expression );
        $code = $this->tag->generator->evaluateExpression( $expression );
        switch ($code) {
            case PHPTAL_TALES_NOTHING_KEYWORD:
                break;

            case PHPTAL_TALES_DEFAULT_KEYWORD:
                $this->tag->generator->pushHtml('<pre class="phptalError"');
                $this->tag->generator->doEcho( '$__err__' );
                $this->tag->generator->pushHtml('</pre>');
                break;
                
            default:
                if ($echoType == 'text')
                    $this->tag->generator->doEcho( $code );
                else
                    $this->tag->generator->pushHtml('<?php echo '.$code.' ?>');
                break;
        }
        $this->tag->generator->doEnd();
    }


    private function parseExpression( $exp )
    {
        $echoType = 'text';
        $expression = trim($exp);

        if (preg_match('/^(text|structure)\s+(.*?)$/ism', $expression, $m)) {
            list(, $echoType, $expression) = $m;
        }

        return array(strtolower($echoType), trim($expression));
    }
}

?>
