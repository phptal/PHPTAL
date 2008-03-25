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
//    * letter - count reps with lower-case letters: "a" - "z", "aa" - "az", "ba" - "bz", ..., "za" - "zz", "aaa" - "aaz", and so forth.
//    * Letter - upper-case version of letter.
//    * roman - count reps with lower-case roman numerals: "i", "ii", "iii", "iv", "v", "vi" ...
//    * Roman - upper-case version of roman numerals.
///
//    * first - true for the first item in a group - see note below
//    * lasst - true for the last item in a group - see note below
//
//  Note: first and last are intended for use with sorted sequences. They try to
//  divide the sequence into group of items with the same value. If you provide
//  a path, then the value obtained by following that path from a sequence item
//  is used for grouping, otherwise the value of the item is used. You can
//  provide the path by appending it to the path from the repeat variable,
//  as in "repeat/item/first/color".
//
// PHPTAL: index, number, even, etc... will be stored in the
// $ctx->repeat->'item'  object.  Thus $ctx->repeat->item->odd
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
        // alias to repeats handler to avoid calling extra getters on each variable access
        $this->tag->generator->doSetVar( self::REPEAT, '$ctx->repeat' );

        list( $varName, $expression ) = $this->parseSetExpression( $this->expression );
        $code = $this->tag->generator->evaluateExpression( $expression );

        $item = '$ctx->' . $varName;
        $controller = self::REPEAT . '->' . $varName;

        // reset item var into template context
        /* // Is this actually needed?
        $this->tag->generator->doIf( '!isset('.$this->item.')' );
        $this->tag->generator->doSetVar( $this->item, 'false' );
        $this->tag->generator->doEnd();
        */

        // instantiate controller using expression
        $this->tag->generator->doSetVar( $controller, 'new PHPTAL_RepeatController('.$code.')' );

        // Lets loop the iterator with a foreach construct
        $this->tag->generator->doForeach( $item, $controller );
    }
        
    public function end()
    {
        $this->tag->generator->doEnd();
    }
           
    private $item;
    private $controller;
}

?>
