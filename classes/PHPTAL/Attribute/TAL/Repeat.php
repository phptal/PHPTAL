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
// $tpl->repeat->'item'  object.  Thus $tpl->repeat->item->odd
// letter and Letter is not supported
//


/**
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_Repeat extends PHPTAL_Attribute
{
    public function start()
    {
        list($this->varName, $expression) = $this->parseExpression($this->expression);
        $code = $this->tag->generator->evaluateExpression($expression);

        $this->tag->generator->pushCode('$__repeat__ = $tpl->repeat()');
        $this->tag->generator->pushCode('if (!isset($tpl->'.$this->varName.')) $tpl->'.$this->varName.' = false');
        $init = sprintf('$__repeat__->%s = new PHPTAL_RepeatController(%s)', $this->varName, $code);
        $this->tag->generator->pushCode($init);
       
        $this->tag->generator->doForeach('$tpl->'.$this->varName, $this->repeatVar('source'));
        
        $this->setRepeatVar('index', $this->repeatVar('index').'+1');
        $this->setRepeatVar('number', $this->repeatVar('number').'+1');
        $this->setRepeatVar('even', $this->repeatVar('index') . ' %2 == 0');
        $this->setRepeatVar('odd', '!' . $this->repeatVar('even'));

        $condition = sprintf('%s == %s',
                             $this->repeatVar('number'), 
                             $this->repeatVar('length')
                             );
        
        $this->tag->generator->doIf( $condition );
        $this->setRepeatVar('end', 'true');
        $this->tag->generator->doEnd();
    }
    
    public function end()
    {
        $this->setRepeatVar('start', 'false');
        $this->tag->generator->doEnd();
    }

    private function parseExpression( $src )
    {
        if (preg_match('/^([a-z][a-z_0-9]*?)\s+(.*?)$/ism', $src, $m)){
            list(,$varName, $expression) = $m;
            return array($varName, $expression);
        }
        throw new Exception("Unable to find item in tal:repeat expression : $src");
    }

    private function repeatVar( $subVar )
    {
        return sprintf('$__repeat__->%s->%s', $this->varName, $subVar);
    }

    private function setRepeatVar( $subVar, $value )
    {
        $code = sprintf('%s = %s', $this->repeatVar($subVar), $value);
        $this->tag->generator->pushCode( $code );
    }

    private $varName;
}

?>
