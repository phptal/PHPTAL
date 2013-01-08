<?php

class PHPTAL_Expr_If extends PHPTAL_Expr_Stmt
{
    function __construct(PHPTAL_Expr $cond)
    {
        $this->condition = $cond;
    }

    function setThen(PHPTAL_Expr_Stmt $then)
    {
        $this->then = $then;
    }

    function setElse(PHPTAL_Expr_Stmt $else)
    {
        $this->else = $else;
    }

    function compiled()
    {
        return 'if('.$this->condition.'){'.
                $this->then.
            '}'.
            ($this->else?'else{'.
                $this->else.
            '}':'');
    }
}
