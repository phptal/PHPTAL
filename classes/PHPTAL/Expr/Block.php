<?php

class PHPTAL_Expr_Block extends PHPTAL_Expr_Stmt
{
    private $expressions = array();

    function append(PHPTAL_Expr_Stmt $expr, $indent='')
    {
        $this->expressions[] = array($indent, $expr);
    }

    private function isExpressionEchoOfStrings(PHPTAL_Expr_Stmt $e)
    {
        if ($e instanceof PHPTAL_Expr_Comment) return true;

        if (!$e instanceof PHPTAL_Expr_Echo) return false;

        foreach($e->subexpressions as $subexpr) {
            if (!$subexpr instanceof PHPTAL_Expr_String) return false;
        }

        return true;
    }

    function removePrecedingEcho()
    {
        $result='';
        // change echo to HTML from beginning of code
        foreach($this->expressions as $k => $codeLine) {
            if (!$this->isExpressionEchoOfStrings($codeLine[1])) break;
            if ($codeLine[1] instanceof PHPTAL_Expr_Comment) continue;

            foreach($codeLine[1]->subexpressions as $subexpr) {
                $result .= $subexpr->getStringValue();
            }

            unset($this->expressions[$k]);
        }
        return $result;
    }

    function removeFollowingEcho()
    {
        // change echo to HTML from end of code
        $end_result = '';
        foreach(array_reverse($this->expressions,true) as $k => $codeLine) {
            if (!$this->isExpressionEchoOfStrings($codeLine[1])) break;
            if ($codeLine[1] instanceof PHPTAL_Expr_Comment) continue;

            foreach($codeLine[1]->subexpressions as $subexpr) {
                $end_result = $subexpr->getStringValue() . $end_result;
            }

            unset($this->expressions[$k]);
        }
        return $end_result;
    }


    function compiled()
    {
        if (count($this->expressions) == 0) return '';

        $result = '';

        // output remaining code
        $nl = count($this->expressions)==1 ? " " : "\n";

        $result .= $nl;
        foreach ($this->expressions as $codeLine) {
            $codeLine = $codeLine[0] . $codeLine[1];
            // avoid adding ; after } and {
            if (!preg_match('/[{};]\s*$/', $codeLine)) {
                $codeLine .= ';'.$nl;
            }
            $result .= $codeLine;
        }
        return $result;
    }
}
