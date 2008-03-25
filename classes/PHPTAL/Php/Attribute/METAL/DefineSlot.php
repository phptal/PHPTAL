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
//          <?php echo $tpl->slots->links ? >
//        <?php else: ? >  
//        <td>
//          <a href="/">A Link</a>
//        </td></tr>
//      </table>
//  <?php } ? >
//

/**
 * @package phptal.php.attribute.metal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_METAL_DefineSlot extends PHPTAL_Php_Attribute
{
    public function start()
    {
        $cond = sprintf('$ctx->hasSlot("%s")', $this->expression);
        $this->tag->generator->doIf($cond);
        $code = sprintf('<?php echo $ctx->getSlot("%s") ?>', $this->expression);
        $this->tag->generator->pushHtml($code);
        $this->tag->generator->doElse();
    }
    
    public function end()
    {
        $this->tag->generator->doEnd();
    }
}

?>
