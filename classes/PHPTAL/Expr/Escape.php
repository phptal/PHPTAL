<?php

class PHPTAL_Expr_Escape extends PHPTAL_Expr
{
    function __construct(PHPTAL_Expr $expr)
    {
        $this->expr = $expr;
    }

    function optimized()
    {
        $this->expr = $this->expr->optimized();
        if ($this->expr instanceof PHPTAL_Expr_String) {
            return new PHPTAL_Expr_String(htmlspecialchars($this->expr->getStringValue(), ENT_QUOTES));
        }
        return $this;
    }

    function compiled()
    {
        return 'phptal_escape('.$this->expr.')';
    }
}