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
// <?php echo phptal_macro($tpl, 'master_page.html/macros/sidebar'); ? >
// 
 
/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_METAL_FillSlot extends PHPTAL_Attribute
{
    public function start()
    {
        $this->tag->generator->pushCode('ob_start()');
    }

    public function end()
    {
        $code = sprintf('$ctx->fillSlot("%s", ob_get_contents()); ob_end_clean()', $this->expression);
        $this->tag->generator->pushCode($code);
    }
}

?>
