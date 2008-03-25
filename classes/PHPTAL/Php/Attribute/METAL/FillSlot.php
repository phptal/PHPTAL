<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
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

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

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
 * @package phptal.php.attribute.metal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_METAL_FillSlot extends PHPTAL_Php_Attribute
{
    public function start()
    {
        $this->tag->generator->pushCode('ob_start()');
    }

    public function end()
    {
        $code = '$ctx->fillSlot("'.$this->expression.'", ob_get_clean())';
        $this->tag->generator->pushCode($code);
    }
}


