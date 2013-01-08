<?php

class PHPTAL_Expr_Echo extends PHPTAL_Expr_Stmt
{
    public $subexpressions;

    function __construct(/* $subexpressions...*/)
    {
        $this->subexpressions = func_get_args();
        foreach($this->subexpressions as $s) assert('$s instanceof PHPTAL_Expr');
    }

    function optimized()
    {
        $newexpressions = array();
        $laststr = NULL;
        foreach($this->subexpressions as $e)
        {
            $e = $e->optimized();
            if ($e instanceof PHPTAL_Expr_Append) {
                $newexpressions = array_merge($newexpressions, $e->getSubexpressions());
                continue;
            }

            if ($e instanceof PHPTAL_Expr_String) {
                if ($e instanceof PHPTAL_Expr_String) {
                    $lastexpr = end($newexpressions);
                    if ($lastexpr instanceof PHPTAL_Expr_String) {
                        array_pop($newexpressions);
                        $newexpressions[] = new PHPTAL_Expr_String($lastexpr->getStringValue() . $e->getStringValue());
                        continue;
                    }
                }
            }
            $newexpressions[] = $e;
        }
        $this->subexpressions = $newexpressions;

        return $this;
    }

    function appendEcho(PHPTAL_Expr_Echo $expr)
    {
        $this->subexpressions = array_merge($this->subexpressions, $expr->subexpressions);
    }

    function append(PHPTAL_Expr $expr)
    {
        $this->subexpressions[] = $expr;
    }

    function compiled()
    {
        if (!$this->subexpressions) return '';
        return 'echo '.implode(',',$this->subexpressions).';';
    }
}
