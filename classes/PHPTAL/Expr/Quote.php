<?php

class PHPTAL_Expr_Quote extends PHPTAL_Expr
{
    function __construct($value, $mode, $encoding)
    {
        if ($encoding == 'UTF-8') // HTML 5: 8.1.2.3 Attributes ; http://code.google.com/p/html5lib/issues/detail?id=93
        {
            // regex excludes unicode control characters, all kinds of whitespace and unsafe characters
            // and trailing / to avoid confusion with self-closing syntax
            $this->unsafe_attr_regex = '/^$|[&=\'"><\s`\pM\pC\pZ\p{Pc}\p{Sk}]|\/$/u';
        } else {
            $this->unsafe_attr_regex = '/^$|[&=\'"><\s`\0177-\377]|\/$/';
        }

        $this->mode = $mode;
        $this->value = $value;
    }

    function optimized()
    {
        $this->value = $this->value->optimized();
        if ($this->isUnquotedStringSafe($this->value)) return $this->value;

        return $this;
    }

    function compiled()
    {
        if ($this->isUnquotedStringSafe($this->value)) {
            return $this->value->compiled();
        }

        $val = new PHPTAL_Expr_Append(
            new PHPTAL_Expr_String('"'),
            $this->value,
            new PHPTAL_Expr_String('"'));

        return $val->optimized()->compiled();
    }

    private function isUnquotedStringSafe(PHPTAL_Expr $value)
    {
        if ($value instanceof PHPTAL_Expr_String) {
            return $this->mode == PHPTAL::HTML5 && !preg_match($this->unsafe_attr_regex, $value->getStringValue());
        }
        return false;
    }
}
