<?php

// TAL Specifications 1.4
//
//   argument ::= [expression]
//
// Example:
//
//      <div tal:omit-tag="" comment="This tag will be removed">
//          <i>...but this text will remain.</i>
//      </div>
//
//      <b tal:omit-tag="not:bold">I may not be bold.</b>
//
// To leave the contents of a tag in place while omitting the surrounding
// start and end tag, use the omit-tag statement. 
//
// If its expression evaluates to a false value, then normal processing 
// of the element continues. 
//
// If the expression evaluates to a true value, or there is no
// expression, the statement tag is replaced with its contents. It is up to
// the interface between TAL and the expression engine to determine the
// value of true and false. For these purposes, the value nothing is false,
// and cancellation of the action has the same effect as returning a
// false value.
// 

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_OmitTag extends PHPTAL_Attribute
{
    function start()
    {
        $this->tag->headFootDisabled = true;
    }

    function end(){}
}

?>
