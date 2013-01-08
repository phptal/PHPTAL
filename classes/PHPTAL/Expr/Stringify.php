<?php

class PHPTAL_Expr_Stringify extends PHPTAL_Expr
{
    function __construct(PHPTAL_Expr $expr)
    {
        assert('!$expr instanceof PHPTAL_Expr_Echo');
        $this->expr = $expr;
    }

    function optimized()
    {
        $this->expr = $this->expr->optimized();
        assert('!$this->expr instanceof PHPTAL_Expr_Echo');

        if ($this->expr instanceof PHPTAL_Expr_String || $this->expr instanceof PHPTAL_Expr_Escape || $this->expr instanceof PHPTAL_Expr_Append) {
            return $this->expr;
        }
        return $this;
    }

    function compiled()
    {
        return 'phptal_tostring('.$this->expr.')';
    }
}
