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

/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Attribute_PHPTAL_ID extends PHPTAL_Attribute
{
    public function start()
    {
        $id = $this->expression;
        $code = '$trigger = $tpl->getTrigger("%s")';
        $code = sprintf($code, str_replace('"', '\\\"', $id));
        $this->tag->generator->pushCode($code);
        $this->tag->generator->doIf('$trigger && $trigger->start($tpl) == PHPTAL_Trigger::PROCEED');
    }

    public function end()
    {
        $this->tag->generator->doEnd();
        $code = 'if ($trigger) $trigger->end($tpl)';
        $this->tag->generator->pushCode($code);
    }
}

?>
