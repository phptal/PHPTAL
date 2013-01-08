<?php

class PHPTAL_Expr_PHP extends PHPTAL_Expr
{
    public $subexpressions;

    function __construct(/* $subexpressions...*/)
    {
        $subexpressions = func_get_args();
        foreach($subexpressions as $s) assert('is_string($s) || $s instanceof PHPTAL_Expr');
        $this->subexpressions = $subexpressions;
    }

    function append(PHPTAL_Expr_Stmt $expr)
    {
        $this->subexpressions[] = $expr;
    }

    function optimized()
    {
        foreach($this->subexpressions as &$s) {
            if ($s instanceof PHPTAL_Expr_Stmt) $s = $s->optimized();
        }

        if (count($this->subexpressions) == 1 && $this->subexpressions[0] instanceof PHPTAL_Expr_Stmt) {
            return $this->subexpressions[0];
        }
        return $this;
    }

    function compiled()
    {
        return implode('', $this->subexpressions);
    }
}
