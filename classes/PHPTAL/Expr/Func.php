<?php

class PHPTAL_Expr_Func extends PHPTAL_Expr_Stmt
{
    function __construct($name, $arglist)
    {
        $this->name = $name;
        $this->arglist = $arglist;
        $this->body = new PHPTAL_Expr_Block(PHPTAL_Expr_Block::BRACES);
    }

    function getBodyBlock()
    {
        return $this->body;
    }

    function optimized()
    {
        $this->body = $this->body->optimized();
        return $this;
    }

    function compiled()
    {
        return 'function '.$this->name.'('.$this->arglist.') '.$this->body;
    }
}
