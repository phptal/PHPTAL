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

// TAL Specifications 1.4
//
//   argument ::= [expression]
//
// Example:
//
//      <div tal:omit-tag="" comment="This tag will be removed">
//          <i>...but this text will remain.</i>
//      </div>
//
//      <b tal:omit-tag="not:bold">I may not be bold.</b>
//
// To leave the contents of a tag in place while omitting the surrounding
// start and end tag, use the omit-tag statement. 
//
// If its expression evaluates to a false value, then normal processing 
// of the element continues. 
//
// If the expression evaluates to a true value, or there is no
// expression, the statement tag is replaced with its contents. It is up to
// the interface between TAL and the expression engine to determine the
// value of true and false. For these purposes, the value nothing is false,
// and cancellation of the action has the same effect as returning a
// false value.
// 

/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_OmitTag extends PHPTAL_Php_Attribute
{
    private static $temp_var_num;
    
    public function start()
    {
        if (trim($this->expression) == ''){
            $this->tag->headFootDisabled = true;
        }
        else { 
            
            $varname = '$_omit'.self::$temp_var_num++;
            
            // print tag header/foot only if condition is false
            $cond = $this->tag->generator->evaluateExpression($this->expression);
            $this->tag->headPrintCondition = '('.$varname.' = !('.$cond.'))';
            $this->tag->footPrintCondition = $varname;
        }
    }

    public function end()
    {
    }
}

?>
