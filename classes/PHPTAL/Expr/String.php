<?php

class PHPTAL_Expr_String extends PHPTAL_Expr
{
    private $string;
    function __construct($string)
    {
        assert('is_string($string)');
        $this->string = $string;
    }

    function getStringValue()
    {
        return $this->string;
    }

    function compiled()
    {
        return "'".strtr($this->string,array("'"=>'\\\'','\\'=>'\\\\'))."'";
    }
}
