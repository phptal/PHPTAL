<?php

// METAL Specification 1.0
//
//      argument ::= Name
//
// Example:
//
//      <p metal:define-macro="copyright">
//      Copyright 2001, <em>Foobar</em> Inc.
//      </p>
//
// PHPTAL:
//      
//      <?php function XXX_macro_copyright( $tpl ) { ? >
//        <p>
//        Copyright 2001, <em>Foobar</em> Inc.
//        </p>
//      <?php } ? >
//

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_METAL_DefineMacro extends PHPTAL_Attribute
{
    function start()
    {
        $this->tag->generator->doFunction($this->expression, '$tpl');
        $doctype = $this->tag->generator->getDocType();
        if ($doctype) {
            $code = sprintf('$tpl->setDocType(\'%s\')', str_replace('\'', '\\\'', $doctype));
            $this->tag->generator->pushCode($code);
        }
        
        $this->tag->generator->pushCode('$tpl = clone $tpl');
        $this->tag->generator->pushCode('$tpl->repeat = clone $tpl->repeat');
    }
    
    function end()
    {
        $this->tag->generator->doEnd();
    }
}

?>
