<?php

class PHPTAL_Expr_Comment extends PHPTAL_Expr
{
    function __construct($comment)
    {
        $this->comment = $comment;
    }

    function compiled()
    {
        return "/* ".str_replace('*/', '* /', $this->comment)." */";
    }
}
