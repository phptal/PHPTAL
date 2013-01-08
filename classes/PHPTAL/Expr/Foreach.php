<?php

class PHPTAL_Expr_Foreach extends PHPTAL_Expr_Stmt
{
    function __construct($var, $source)
    {
        $this->var = $var; $this->source = $source;
        $this->block = new PHPTAL_Expr_Block(PHPTAL_Expr_Block::BRACES);
    }

    function getBlock()
    {
        return $this->block;
    }

    function compiled()
    {
        return "foreach(".$this->source." as ".$this->var.")".$this->block;
    }
}
