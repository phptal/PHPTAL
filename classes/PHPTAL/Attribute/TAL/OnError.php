<?php

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
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_OnError extends PHPTAL_Attribute
{
    function start()
    {
        $this->tag->generator->doTry();
        $this->tag->generator->pushCode('ob_start()');
    }
    
    function end()
    {
        $this->tag->generator->pushCode('ob_end_flush()');        
        $this->tag->generator->doCatch('Exception $__err__');
        $this->tag->generator->pushCode('$tpl->errors[] = $__err__');
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
                    $this->tag->generator->pushHtml('<?= '.$code.' ?>');
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
