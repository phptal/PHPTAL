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
 * @package phptal.php.attribute.phptal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_PHPTAL_ID extends PHPTAL_Php_Attribute
{
    private $var;
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        // retrieve trigger
        $this->var = $codewriter->createTempVariable();        
                
        $codewriter->doSetVar(
            $this->var, 
            '$tpl->getTrigger('.$codewriter->str($this->expression).')'
        );

        // if trigger found and trigger tells to proceed, we execute
        // the node content
        $codewriter->doIf($this->var.' && 
            '.$this->var.'->start('.$codewriter->str($this->expression).', $tpl) === PHPTAL_Trigger::PROCEED');
    }

    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
        // end of if PROCEED
        $codewriter->doEnd();
        
        // if trigger found, notify the end of the node
        $codewriter->doIf($this->var);
        $codewriter->pushCode(
            $this->var.'->end('.$codewriter->str($this->expression).', $tpl)'
        );
        $codewriter->doEnd();
        $codewriter->recycleTempVariable($this->var);
    }
}

