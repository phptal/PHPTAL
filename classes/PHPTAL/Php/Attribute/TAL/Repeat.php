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

require_once 'PHPTAL/Php/Attribute.php';

// TAL Specifications 1.4
//
//      argument      ::= variable_name expression
//      variable_name ::= Name
//
// Example:
//
//      <p tal:repeat="txt python:'one', 'two', 'three'">
//         <span tal:replace="txt" />
//      </p>
//      <table>
//        <tr tal:repeat="item here/cart">
//            <td tal:content="repeat/item/index">1</td>
//            <td tal:content="item/description">Widget</td>
//            <td tal:content="item/price">$1.50</td>
//        </tr>
//      </table>
//
// The following information is available from an Iterator:
//
//    * index - repetition number, starting from zero.
//    * number - repetition number, starting from one.
//    * even - true for even-indexed repetitions (0, 2, 4, ...).
//    * odd - true for odd-indexed repetitions (1, 3, 5, ...).
//    * start - true for the starting repetition (index 0).
//    * end - true for the ending, or final, repetition.
//    * length - length of the sequence, which will be the total number of repetitions.
//
//    
//    * letter - count reps with lower-case letters: "a" - "z", "aa" - "az", "ba" - "bz", ..., "za" - "zz", "aaa" - "aaz", and so forth.
//    * Letter - upper-case version of letter.
//
// PHPTAL: index, number, even, etc... will be stored in the
// $ctx->repeat->'item'  object.  Thus $ctx->repeat->item->odd
// letter and Letter is not supported
//


/**
 * @package phptal.php.attribute.tal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_Attribute_TAL_Repeat extends PHPTAL_Php_Attribute
{
    const REPEAT = '$__repeat__';
    
    public function start()
    {
        $this->initRepeat();
        $this->doForeach();
        $this->updateIterationVars();
    }
        
    public function end()
    {
        $this->tag->generator->doSetVar($this->controller.'->start', 'false');
        $this->tag->generator->doEnd();
    }

    private function initRepeat()
    {
        list($varName, $expression) = $this->parseSetExpression($this->expression);
        $code = $this->tag->generator->evaluateExpression($expression);
        
        $this->item       = '$ctx->'.$varName;
        $this->controller = self::REPEAT.'->'.$varName;

        // alias to repeats handler to avoid calling extra getters on each variable access
        $this->tag->generator->doSetVar(self::REPEAT, '$ctx->repeat');
        
        // reset item var into template context
        $this->tag->generator->doIf('!isset('.$this->item.')');
        $this->tag->generator->doSetVar($this->item, 'false');
        $this->tag->generator->doEnd();

        // instantiate controller using expression
	    	$this->tag->generator->doSetVar($this->controller, 'new PHPTAL_RepeatController('.$code.')');
    }
       
    private function doForeach()
    {
        $this->tag->generator->doForeach($this->item, $this->controller.'->source');
    }
    
    private function updateIterationVars()
    {
        $this->tag->generator->doSetVar($this->controller.'->key', '$__key__');
        $this->tag->generator->doSetVar($this->controller.'->index', $this->controller.'->index +1');
        $this->tag->generator->doSetVar($this->controller.'->number', $this->controller.'->number +1');
        $this->tag->generator->doSetVar($this->controller.'->even', $this->controller.'->index % 2 == 0');
        $this->tag->generator->doSetVar($this->controller.'->odd', '!'.$this->controller.'->even');

        // repeat/item/end set to true when last item is reached
        $this->tag->generator->doIf($this->controller.'->number == '.$this->controller.'->length');
        $this->tag->generator->doSetVar($this->controller.'->end', 'true');
        $this->tag->generator->doEnd();
    }
    
    private $item;
    private $controller;
}

?>
