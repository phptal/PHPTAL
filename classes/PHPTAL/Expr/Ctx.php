<?php

class PHPTAL_Expr_Ctx extends PHPTAL_Expr_Var
{
    private $prop;
    function __construct(PHPTAL_Expr $prop)
    {
        $this->prop = $prop;
    }

    function optimized()
    {
        $this->prop = $this->prop->optimized();
        return $this;
    }

    function compiled()
    {
        if ($this->prop instanceof PHPTAL_Expr_String) {
            $val = $this->prop->getStringValue();
            if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/',$val)) {
                return '$ctx->'.$val;
            }
        }
        return '$ctx->{'.$this->prop.'}';
    }
}
