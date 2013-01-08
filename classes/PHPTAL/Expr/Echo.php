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
        foreach($this->subexpressions as $k => &$s) {
            $s = $s->optimized();

            if ($s instanceof PHPTAL_Expr_String && '' === $s->getStringValue()) unset($this->subexpressions[$k]);
        }

        $prepend = array();
        foreach($this->subexpressions as $k => $expr) {
            if (!$expr instanceof PHPTAL_Expr_Append) break;
            $prepend = array_merge($prepend, $expr->getSubexpressions());
            unset($this->subexpressions[$k]);
        }
        $this->subexpressions = array_merge($prepend, $this->subexpressions);


        return $this;
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
