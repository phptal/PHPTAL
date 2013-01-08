<?php

class PHPTAL_Expr_If extends PHPTAL_Expr_Stmt
{
    private $condition,$then,$else;
    function __construct(PHPTAL_Expr $cond)
    {
        $this->condition = $cond;
        $this->then = new PHPTAL_Expr_Block(PHPTAL_Expr_Block::BRACES);
    }

    function getThenBlock(){return $this->then;}
    function getElseBlock(){return $this->else = new PHPTAL_Expr_Block(PHPTAL_Expr_Block::BRACES);}

    function optimized()
    {
        $this->then = $this->then->optimized();
        if ($this->else) $this->else = $this->else->optimized();
        return $this;
    }

    function compiled()
    {
        return 'if('.$this->condition.')'.$this->then.
                ($this->else?'else'.$this->else:'');
    }
}
