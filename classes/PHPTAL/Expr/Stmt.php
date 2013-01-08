<?php

abstract class PHPTAL_Expr_Stmt
{
    abstract function compiled();

    function optimized()
    {
        return $this;
    }

    function __toString()
    {
        return $this->compiled();
    }

    static function debug(PHPTAL_Expr_Stmt $e)
    {
        return '/*'.strtr(print_r($e,true), array('?>'=>'? >', '*/'=>'* /')).'*/';
    }
}
