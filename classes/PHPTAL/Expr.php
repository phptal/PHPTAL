<?php

abstract class PHPTAL_Expr extends PHPTAL_Expr_Stmt
{
}


class PHPTAL_Expr_Nothing extends PHPTAL_Expr
{
    function compiled() {assert(0);}
}

class PHPTAL_Expr_Default extends PHPTAL_Expr
{
    function compiled() {assert(0);}
}
