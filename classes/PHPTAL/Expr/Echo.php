<?php

class PHPTAL_Expr_Echo extends PHPTAL_Expr
{
    public $subexpressions;

    function __construct(/* $subexpressions...*/)
    {
        $this->subexpressions = func_get_args();
        foreach($this->subexpressions as $s) assert('$s instanceof PHPTAL_Expr');
    }

    function optimized()
    {
        foreach($this->subexpressions as $k => &$s) {
            $s = $s->optimized();

            if ($s instanceof PHPTAL_Expr_String && '' === $s->getStringValue()) unset($this->subexpressions[$k]);
        }
        return $this;
    }

    function append(PHPTAL_Expr $expr)
    {
        if ($expr instanceof PHPTAL_Expr_Append) {
            $expr = $expr->optimized();
            if ($expr instanceof PHPTAL_Expr_Append) {
                $this->subexpressions = array_merge($this->subexpressions, $expr->getSubexpressions());
                return;
            }
        }

        $this->subexpressions[] = $expr;
    }

    function compiled()
    {
        if (!$this->subexpressions) return '';
        return 'echo '.implode(',',$this->subexpressions).';';
    }
}
