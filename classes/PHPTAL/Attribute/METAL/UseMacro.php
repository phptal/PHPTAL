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
//      <?php echo phptal_macro( $tpl, 'master_page.html/macros/copyright'); ? >
//

/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_METAL_UseMacro extends PHPTAL_Attribute
{
    public function start()
    {
        // reset template slots on each macro call ?
        // note: defining and calling a macro on the same tag means inherit
        // from the macro, thus slots are shared
        if (!$this->tag->hasPhpTalAttribute('metal:define-macro')){
            $this->tag->generator->pushCode('$ctx->pushSlots()');
        }
        
        foreach ($this->tag->children as $child){
            $this->generateFillSlots($child);
        }

        if (preg_match('/^[a-z0-9_]+$/i', $this->expression)){
            $code = sprintf('%s%s($tpl, $ctx)', 
                            $this->tag->generator->getFunctionPrefix(),
                            $this->expression);
            $this->tag->generator->pushCode($code);
        }
        else {
            $code = $this->tag->generator->evaluateTalesString($this->expression);
            $code = sprintf('<?php $tpl->executeMacro(%s); ?>', $code);
            $this->tag->generator->pushHtml($code);
        }

        if (!$this->tag->hasPhpTalAttribute('metal:define-macro')){
            $this->tag->generator->pushCode('$ctx->popSlots()');
        }
    }
    
    public function end()
    {
    }

    private function generateFillSlots($tag)
    {
        $allowedAtts = array( 'metal:fill-slot', 'metal:define-macro', 'tal:define' );
                              
        if (! $tag instanceOf PHPTAL_NodeTree ) return;

        foreach ($allowedAtts as $attribute){
            if ($tag->hasPhpTalAttribute($attribute)){
                $tag->generate();
                return;
            }
        }
        
        foreach ($tag->children as $child){
            $this->generateFillSlots($child);
        }
    }
}

?>
