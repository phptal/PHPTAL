<?php

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
 * @package PHPTAL
 */
class PHPTAL_Attribute_TAL_Repeat extends PHPTAL_Attribute
{
    public function start()
    {
        list($this->varName, $expression) = $this->parseExpression($this->expression);
        $code = $this->tag->generator->evaluateExpression($expression);

        $init = sprintf('$tpl->repeat->%s = new PHPTAL_RepeatController()', $this->varName);
        $this->tag->generator->pushCode($init);
        
        $this->setRepeatVar('source', $code);
        $this->setRepeatVar('index', '-1');
        $this->setRepeatVar('number', '0');
        $this->setRepeatVar('start', 'true');
        $this->setRepeatVar('end', 'false');
        $this->setRepeatVar('length', 'phptal_repeat_size('.$this->repeatVar('source').')');
        
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
        return sprintf('$tpl->repeat->%s->%s', $this->varName, $subVar);
    }

    private function setRepeatVar( $subVar, $value )
    {
        $code = sprintf('%s = %s', $this->repeatVar($subVar), $value);
        $this->tag->generator->pushCode( $code );
    }

    private $varName;
}

?>
