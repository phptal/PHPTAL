<?php

class PHPTAL_Expr_Var extends PHPTAL_Expr
{
    private $var;
    function __construct($var)
    {
        assert('is_string($var) && $var[0]===\'$\'');
        $this->var = $var;
    }

    function compiled()
    {
        return $this->var;
    }
}
