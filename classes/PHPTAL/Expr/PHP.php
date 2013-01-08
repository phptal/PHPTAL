<?php

abstract class PHPTAL_Expr
{
    abstract function compiled();

    function optimized()
    {
        return $this;
    }

    function __toString()
    {
        return $this->compiled();
    }
}

class PHPTAL_Expr_PHP extends PHPTAL_Expr
{
    public $subexpressions;

    function __construct(/* $subexpressions...*/)
    {
        $subexpressions = func_get_args();
        foreach($subexpressions as $s) assert('is_string($s) || $s instanceof PHPTAL_Expr');
        $this->subexpressions = $subexpressions;
    }

    function append(PHPTAL_Expr $expr)
    {
        $this->subexpressions[] = $expr;
    }

    function optimized()
    {
        foreach($this->subexpressions as &$s) {
            if ($s instanceof PHPTAL_Expr) $s = $s->optimized();
        }

        if (count($this->subexpressions) == 1 && $this->subexpressions[0] instanceof PHPTAL_Expr) {
            return $this->subexpressions[0];
        }
        return $this;
    }

    function compiled()
    {
        return implode('', $this->subexpressions);
    }
}

class PHPTAL_Expr_Nothing extends PHPTAL_Expr
{
    function compiled() {assert(0);}
}

class PHPTAL_Expr_Default extends PHPTAL_Expr
{
    function compiled() {assert(0);}
}
