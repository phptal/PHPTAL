<?php

// METAL Specification 1.0
//
//      argument ::= expression
//
// Example:
// 
//      <hr />
//      <p metal:use-macro="here/master_page/macros/copyright">
//      <hr />
//
// PHPTAL: (here not supported)
//
//      <?= phptal_macro( $tpl, 'master_page.html/macros/copyright'); ? >
//

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_METAL_UseMacro extends PHPTAL_Attribute
{
    function start()
    {
        // reset template slots on each macro call ?
        $this->tag->generator->pushCode('array_push($tpl->slotsStack, $tpl->slots)');
        $this->tag->generator->pushCode('$tpl->slots = array()');
        
        foreach ($this->tag->children as $child){
            $this->generateFillSlots($child);
        }

        if (preg_match('/^[a-z0-9_]+$/', $this->expression)){
            $code = sprintf('%s%s($tpl)', 
                            $this->tag->generator->getFunctionPrefix(),
                            $this->expression);
            $this->tag->generator->pushCode($code);
        }
        else {
            $code = phptal_tales_string($this->expression);
            $code = sprintf('<?php $tpl->executeMacro(%s); ?>', $code);
            $this->tag->generator->pushHtml($code);
        }
        $this->tag->generator->pushCode('$tpl->slots = array_pop($tpl->slotsStack)');
    }
    
    function end()
    {
    }

    private function generateFillSlots($tag)
    {
        if (! $tag instanceOf PHPTAL_NodeTree ) return;
        if (array_key_exists('metal:fill-slot', $tag->attributes)){
            $tag->generate();
            return;
        }
        if (array_key_exists('tal:define', $tag->attributes)){
            $tag->generate();
            return;
        }
        
        foreach ($tag->children as $child){
            $this->generateFillSlots($child);
        }
    }
}

?>
