<?php

// METAL Specification 1.0
//
//      argument ::= Name
//
// Example:
//
//       <table metal:use-macro="here/doc1/macros/sidebar">
//        <tr><th>Links</th></tr>
//        <tr><td metal:fill-slot="links">
//          <a href="http://www.goodplace.com">Good Place</a><br>
//          <a href="http://www.badplace.com">Bad Place</a><br>
//          <a href="http://www.otherplace.com">Other Place</a>
//        </td></tr>
//      </table>
//
// PHPTAL: 
// 
// 1. evaluate slots
// 
// <?php ob_start(); ? >
// <td>
//   <a href="http://www.goodplace.com">Good Place</a><br>
//   <a href="http://www.badplace.com">Bad Place</a><br>
//   <a href="http://www.otherplace.com">Other Place</a>
// </td>
// <?php $tpl->slots->links = ob_get_contents(); ob_end_clean(); ? >
// 
// 2. call the macro (here not supported)
//
// <?= phptal_macro($tpl, 'master_page.html/macros/sidebar'); ? >
// 
 
/**
 * @package PHPTAL
 */
class PHPTAL_Attribute_METAL_FillSlot extends PHPTAL_Attribute
{
    function start()
    {
        $this->tag->generator->pushCode('ob_start()');
    }

    function end()
    {
        $code = sprintf('$tpl->slots["%s"] = ob_get_contents(); ob_end_clean()', $this->expression);
        $this->tag->generator->pushCode($code);
    }
}

?>
