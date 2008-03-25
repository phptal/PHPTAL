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

/**
 * @package phptal.php.attribute.phptal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_PHPTAL_ID extends PHPTAL_Php_Attribute
{
    private $id;
    
    public function start()
    {
        $this->id = str_replace('"', '\\\"', $this->expression);
        
        // retrieve trigger
        $this->tag->generator->doSetVar(
            '$trigger', 
            '$tpl->getTrigger("'.$this->id.'")'
        );

        // if trigger found and trigger tells to proceed, we execute
        // the node content
        $cond = '$trigger && '
              . '$trigger->start("%s", $tpl) == PHPTAL_Trigger::PROCEED';
        $cond = sprintf($cond, $this->id);

        $this->tag->generator->doIf($cond);
    }

    public function end()
    {
        // end of if PROCEED
        $this->tag->generator->doEnd();
        
        // if trigger found, notify the end of the node
        $this->tag->generator->doIf('$trigger');
        $this->tag->generator->pushCode(
            '$trigger->end("'.$this->id.'", $tpl)'
        );
        $this->tag->generator->doEnd();
    }
}

?>
