<?php

class PHPTAL_Expr_Try extends PHPTAL_Expr_Stmt
{
    private $block, $catches = array();
    function __construct()
    {
        $this->block = new PHPTAL_Expr_Block(PHPTAL_Expr_Block::BRACES);
    }

    function getBlock() {return $this->block;}
    function addCatchBlock($exc, $var)
    {
        $block = new PHPTAL_Expr_Block(PHPTAL_Expr_Block::BRACES);
        $this->catches[] = array($exc, $var, $block);

        return $block;
    }

    function optimized()
    {
        $this->block = $this->block->optimized();
        foreach($this->catches as &$c) $c[2] = $c[2]->optimized();
        return $this;
    }

    function compiled()
    {
        $code = "try".$this->block->compiled();
        foreach($this->catches as $tmp) {
            list($exc, $var, $block) = $tmp;
            $code .= "catch($exc $var) $block";
        }
        return $code;
    }
}
