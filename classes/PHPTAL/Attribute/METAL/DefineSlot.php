<?php

// METAL Specification 1.0
//
//      argument ::= Name
//
// Example:
//
//      <table metal:define-macro="sidebar">
//        <tr><th>Links</th></tr>
//        <tr><td metal:define-slot="links">
//          <a href="/">A Link</a>
//        </td></tr>
//      </table>
//
// PHPTAL: (access to slots may be renamed)
//
//  <?php function XXXX_macro_sidebar( $tpl ) { ? >
//      <table>
//        <tr><th>Links</th></tr>
//        <tr>
//        <?php if (isset($tpl->slots->links)): ? >
//          <?= $tpl->slots->links ? >
//        <?php else: ? >  
//        <td>
//          <a href="/">A Link</a>
//        </td></tr>
//      </table>
//  <?php } ? >
//

/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_METAL_DefineSlot extends PHPTAL_Attribute
{
    function start()
    {
        $cond = sprintf('array_key_exists("%s", $tpl->slots)', $this->expression);
        $this->tag->generator->doIf($cond);
        $code = sprintf('<?= $tpl->slots["%s"] ?>', $this->expression);
        $this->tag->generator->pushHtml($code);
        $this->tag->generator->doElse();
    }
    
    function end()
    {
        $this->tag->generator->doEnd();
    }
}

?>
