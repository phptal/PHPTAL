<?php

class PHPTAL_Expr_Stringify extends PHPTAL_Expr
{
    function __construct(PHPTAL_Expr $expr)
    {
        $this->expr = $expr;
    }

    function optimized()
    {
        $this->expr = $this->expr->optimized();
        if ($this->expr instanceof PHPTAL_Expr_String || $this->expr instanceof PHPTAL_Expr_Escape) {
            return $this->expr;
        }
        return $this;
    }

    function compiled()
    {
        return 'phptal_tostring('.$this->expr.')';
    }
}