<?php

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


/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_Condition extends PHPTAL_Attribute
{
    public function start()
    {
        $code = $this->tag->generator->evaluateExpression($this->expression);
        $this->tag->generator->doIf($code);
    }

    public function end() 
    {
        $this->tag->generator->doEnd();
    }
}

