<?php

class PHPTAL_Expr_Block extends PHPTAL_Expr_Stmt
{
    private $braces;
    const NO_BRACES=-1, OPT_BRACES=0, BRACES=1;

    function __construct($braces = self::OPT_BRACES)
    {
        $this->braces = $braces;
    }

    private $expressions = array();

    function append(PHPTAL_Expr_Stmt $expr)
    {
        $this->expressions[] = $expr;
    }

    private function mergeAdjacentEchos()
    {
        $lastexp = NULL;
        foreach($this->expressions as $k => $exp) {
            if ($exp instanceof PHPTAL_Expr_Echo && $lastexp instanceof PHPTAL_Expr_Echo) {
                $lastexp->appendEcho($exp);
                unset($this->expressions[$k]);
            } else {
                $lastexp = $exp;
            }
        }
    }

    function optimized()
    {
        $this->mergeAdjacentEchos();
        foreach($this->expressions as &$e) $e = $e->optimized();

        if ($this->braces === self::NO_BRACES && count($this->expressions) == 1) return $this->expressions[0];
        return $this;
    }

    function compiled()
    {
        if (count($this->expressions) == 0) {
            return $this->braces === self::BRACES ? '{}' : '';
        }

        $result = $this->braces === self::NO_BRACES ? '' : "{\n";

        // output remaining code
        $nl = count($this->expressions)==1 ? " " : "\n";

        $result .= $nl;
        foreach ($this->expressions as $codeLine) {
            assert('$codeLine instanceof PHPTAL_Expr_Stmt');
            $line = $codeLine->compiled();
            // avoid adding ; after } and {
            if (!$codeLine instanceof PHPTAL_Expr_Try &&
                !$codeLine instanceof PHPTAL_Expr_Block &&
                !$codeLine instanceof PHPTAL_Expr_Comment &&
                !$codeLine instanceof PHPTAL_Expr_If &&
                !$codeLine instanceof PHPTAL_Expr_Catch && !preg_match('/[{;]\s*$/', $line)) {

                $line .= ';';
            }
            $result .= $nl.$line;
        }
        return $result . ($this->braces === self::NO_BRACES ? '' : "}\n");
    }
}
