<?php

class PHPTAL_Expr_Append extends PHPTAL_Expr
{
    private $expressions = array();

    function __construct(/*...*/)
    {
        $args = func_get_args();
        foreach($args as $arg) $this->append($arg);
    }

    function append(PHPTAL_Expr $expr)
    {
        $this->expressions[] = $expr;
    }

    public function getSubexpressions()
    {
        return $this->expressions;
    }

    function optimized()
    {
        if (!$this->expressions) return new PHPTAL_Expr_String('');

        $newexpressions = array();
        foreach($this->expressions as $expr) {
            $expr = $expr->optimized();

            if ($expr instanceof PHPTAL_Expr_String) {
                $lastexpr = end($newexpressions);
                if ($lastexpr instanceof PHPTAL_Expr_String) {
                    array_pop($newexpressions);
                    $newexpressions[] = new PHPTAL_Expr_String($lastexpr->getStringValue() . $expr->getStringValue());
                    continue;
                }
            }
            $newexpressions[] = $expr;
        }
        $this->expressions = $newexpressions;

        if (count($this->expressions)==1) return $this->expressions[0];
        return $this;
    }

    function compiled()
    {
        $code = '';
        foreach($this->expressions as $i => $expr)
        {
            if ($i > 0) $code .= '.';
            if ($expr instanceof PHPTAL_Expr_PHP) $expr = '('.$expr.')';
            $code .= $expr;
        }
        return $code;
    }
}
