<?php

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
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_Content extends PHPTAL_Attribute
{
    public function start()
    {
        list($echoType, $expression) = $this->parseExpression($this->expression);
        $code = $this->tag->generator->evaluateExpression( $expression );

        if (is_array($code)) {
            $started = false;
            foreach ($code as $exp){
                if ($exp == PHPTAL_TALES_NOTHING_KEYWORD){
                    continue;
                }
                
                if ($exp == PHPTAL_TALES_DEFAULT_KEYWORD){
                    if ($started)
                        $this->tag->generator->doElse();
                    $this->generateDefault();
                    break;
                }

                $condition = sprintf('$__content__ = %s', $exp);
                if (!$started) {
                    $this->tag->generator->doIf($condition);
                    $started = true;
                }
                else {
                    $this->tag->generator->doElseIf($condition);
                }
                $this->generateContent($echoType, '$__content__');
            }
            if ($started)
                $this->tag->generator->doEnd();
            return;
        }

        if ($code == PHPTAL_TALES_NOTHING_KEYWORD) {
            return;
        }

        if ($code == PHPTAL_TALES_DEFAULT_KEYWORD) {
            $this->generateDefault();
            return;
        }
        
        $this->generateContent( $echoType, $code );
    }
    
    public function end(){}

    private function generateDefault()
    {
        $this->tag->generateContent(true);
    }
    
    private function generateContent( $echoType, $code )
    {
        if ($echoType == 'text') {
            $this->tag->generator->doEcho( $code );
        }
        else {
            $this->tag->generator->pushHtml('<?= '.$code.' ?>');
        }
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
