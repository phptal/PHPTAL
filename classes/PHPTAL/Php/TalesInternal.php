<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Moritz Bechler <mbechler@eenterphace.org>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
*/

require_once 'PHPTAL/Php/Transformer.php';

/**
 * @package PHPTAL
 * @subpackage php
 */
class PHPTAL_Php_TalesInternal implements PHPTAL_Tales
{
    const DEFAULT_KEYWORD = '_DEFAULT_DEFAULT_DEFAULT_DEFAULT_';
    const NOTHING_KEYWORD = '_NOTHING_NOTHING_NOTHING_NOTHING_';
    
    /**
     * This function registers all internal expression modifiers
     */
    static public function registerInternalTales()
    {
        static $registered = false;

        if ($registered) {
            return;
        }

        $registry = PHPTAL_TalesRegistry::getInstance();

        $registry->registerPrefix('not', array(__CLASS__, 'not'));
        $registry->registerPrefix('path', array(__CLASS__, 'path'));
        $registry->registerPrefix('string', array(__CLASS__, 'string'));
        $registry->registerPrefix('php', array(__CLASS__, 'php'));
        $registry->registerPrefix('exists', array(__CLASS__, 'exists'));
        $registry->registerPrefix('number', array(__CLASS__, 'number'));
        $registry->registerPrefix('true', array(__CLASS__, 'true'));

        $registered = true;
    }

    static public function true($src, $nothrow)
    {
        if (ctype_alnum($src)) return '!empty($ctx->'.$src.')';

        return 'phptal_true($ctx, '.self::string(trim($src), $nothrow).')';
    }

    /**
     * not:
     *
     *      not: Expression
     *
     * evaluate the expression string (recursively) as a full expression,
     * and returns the boolean negation of its value
     *
     * return boolean based on the following rules:
     *
     *     1. integer 0 is false
     *     2. integer > 0 is true
     *     3. an empty string or other sequence is false
     *     4. a non-empty string or other sequence is true
     *     5. a non-value (e.g. void, None, Nil, NULL, etc) is false
     *     6. all other values are implementation-dependent.
     *
     * Examples:
     *
     *      not: exists: foo/bar/baz
     *      not: php: object.hasChildren()
     *      not: string:${foo}
     *      not: foo/bar/booleancomparable
     */
    static public function not($expression, $nothrow)
    {
        return '!(' . self::compileToPHPExpression($expression, $nothrow) . ')';
    }


    /**
     * path:
     *
     *      PathExpr  ::= Path [ '|' Path ]*
     *      Path      ::= variable [ '/' URL_Segment ]*
     *      variable  ::= Name
     *
     * Examples:
     *
     *      path: username
     *      path: user/name
     *      path: object/method/10/method/member
     *      path: object/${dynamicmembername}/method
     *      path: maybethis | path: maybethat | path: default
     *
     * PHPTAL:
     *
     * 'default' may lead to some 'difficult' attributes implementation
     *
     * For example, the tal:content will have to insert php code like:
     *
     * if (isset($ctx->maybethis)) {
     *     echo $ctx->maybethis;
     * }
     * elseif (isset($ctx->maybethat) {
     *     echo $ctx->maybethat;
     * }
     * else {
     *     // process default tag content
     * }
     *
     * @returns string or array
     */
    static public function path($expression, $nothrow=false)
    {
        $expression = trim($expression);
        if ($expression == 'default') return self::DEFAULT_KEYWORD;
        if ($expression == 'nothing') return self::NOTHING_KEYWORD;
        if ($expression == '')        return self::NOTHING_KEYWORD;

        // split OR expressions terminated by a string
        if (preg_match('/^(.*?)\s*\|\s*?(string:.*)$/sm', $expression, $m)) {
            list(, $expression, $string) = $m;
        }
        // split OR expressions terminated by a 'fast' string
        elseif (preg_match('/^(.*?)\s*\|\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*$/sm', $expression, $m)) {
            list(, $expression, $string) = $m;
            $string = 'string:'.stripslashes($string);
        }

        // split OR expressions
        $exps = preg_split('/\s*\|\s*/sm', $expression);

        // if (many expressions) or (expressions or terminating string) found then
        // generate the array of sub expressions and return it.
        if (count($exps) > 1 || isset($string)) {
            $result = array();
            foreach ($exps as $exp) {
                $result[] = self::compileToPHPStatements(trim($exp), true);
            }
            if (isset($string)) {
                $result[] = self::compileToPHPStatements($string, true);
            }
            return $result;
        }


        // see if there are subexpressions, but skip interpolated parts, i.e. ${a/b}/c is 2 parts
        if (preg_match('/^((?:[^$\/]+|\$\$|\${[^}]+}|\$))\/(.+)$/', $expression, $m))
        {
            if (!self::checkExpressionPart($m[1]))  throw new PHPTAL_ParserException("Invalid TALES path: '$expression', expected '{$m[1]}' to be variable name");

            $next = self::string($m[1]);
            $expression = self::string($m[2]);
        } else {
            if (!self::checkExpressionPart($expression)) throw new PHPTAL_ParserException("Invalid TALES path: '$expression', expected variable name. Complex expressions need php: modifier.");

            $next = self::string($expression);
            $expression = null;
        }

        if (preg_match('/^\'[a-z][a-z0-9_]*\'$/i', $next)) $next = substr($next,1,-1); else $next = '{'.$next.'}';

        // if no sub part for this expression, just optimize the generated code
        // and access the $ctx->var
        if ($expression === null) {
            return '$ctx->'.$next;
        }

        // otherwise we have to call phptal_path() to resolve the path at runtime
        // extract the first part of the expression (it will be the phptal_path()
        // $base and pass the remaining of the path to phptal_path()
        return 'phptal_path($ctx->'.$next.', '.$expression.($nothrow ? ', true' : '').')';
    }

    /**
     * check if part of exprssion (/foo/ or /foo${bar}/) is alphanumeric
     */
    private static function checkExpressionPart($expression)
    {
        $expression = preg_replace('/\${[^}]+}/', 'a', $expression); // pretend interpolation is done
        return preg_match('/^[a-z_][a-z0-9_]*$/i', $expression);
    }

    /**
     * string:
     *
     *      string_expression ::= ( plain_string | [ varsub ] )*
     *      varsub            ::= ( '$' Path ) | ( '${' Path '}' )
     *      plain_string      ::= ( '$$' | non_dollar )*
     *      non_dollar        ::= any character except '$'
     *
     * Examples:
     *
     *      string:my string
     *      string:hello, $username how are you
     *      string:hello, ${user/name}
     *      string:you have $$130 in your bank account
     */
    static public function string($expression, $nothrow=false)
    {
        // This is a simple parser which evaluates ${foo} inside
        // 'string:foo ${foo} bar' expressions, it returns the php code which will
        // print the string with correct interpollations.
        // Nothing special there :)

        $inPath = false;
        $inAccoladePath = false;
        $lastWasDollar = false;
        $result = '';
        $len = strlen($expression);
        for ($i=0; $i<$len; $i++) {
            $c = $expression[$i];
            switch ($c) {
                case '$':
                    if ($lastWasDollar) {
                        $lastWasDollar = false;
                    } elseif ($inAccoladePath) {
                        $subPath .= $c;
                        $c = '';
                    } else {
                        $lastWasDollar = true;
                        $c = '';
                    }
                    break;

                case '\\':
                    if ($inAccoladePath) {
                        $subPath .= $c;
                        $c = '';
                    }
                    else {
                        $c = '\\\\';
                    }
                    break;

                case '\'':
                    if ($inAccoladePath) {
                        $subPath .= $c;
                        $c = '';
                    }
                    else {
                        $c = '\\\'';
                    }
                    break;

                case '{':
                    if ($inAccoladePath) {
                        $subPath .= $c;
                        $c = '';
                    }
                    elseif ($lastWasDollar) {
                        $lastWasDollar = false;
                        $inAccoladePath = true;
                        $subPath = '';
                        $c = '';
                    }
                    break;

                case '}':
                    if ($inAccoladePath) {
                        $inAccoladePath = false;
                        $subEval = self::compileToPHPExpression($subPath,false);
                        $result .= "'.(" . $subEval . ").'";
                        $subPath = '';
                        $lastWasDollar = false;
                        $c = '';
                    }
                    break;

                default:
                    if ($lastWasDollar) {
                        $lastWasDollar = false;
                        $inPath = true;
                        $subPath = $c;
                        $c = '';
                    } elseif ($inAccoladePath) {
                        $subPath .= $c;
                        $c = '';
                    } elseif ($inPath) {
                        $t = strtolower($c);
                        if (($t >= 'a' && $t <= 'z') || ($t >= '0' && $t <= '9') || ($t == '_')) {
                            $subPath .= $c;
                            $c = '';
                        } else {
                            $inPath = false;
                            $subEval = self::compileToPHPExpression($subPath,false);
                            $result .= "'.(" . $subEval . ").'";
                        }
                    }
                    break;
            }
            $result .= $c;
        }
        if ($inPath) {
            $subEval = self::compileToPHPExpression($subPath,false);
            $result .= "'.(" . $subEval . ").'";
        }

        // optimize ''.foo.'' to foo
        $result = preg_replace("/^(?:''\.)?(.*?)(?:\.'')?$/", '\1', '\''.$result.'\'');

        // optimize (foo()) to foo()
        $result = preg_replace("/^\(((?:[^()]+|\([^()]*\))*)\)$/", '\1', $result);
        return $result;
    }

    /**
     * php: modifier.
     *
     * Transform the expression into a regular PHP expression.
     */
    static public function php($src)
    {
        return PHPTAL_Php_Transformer::transform($src, '$ctx->');
    }

    /**
     * exists: modifier.
     *
     * Returns the code required to invoke phptal_exists() on specified path.
     */
    static public function exists($src, $nothrow)
    {
        if (ctype_alnum($src)) return 'isset($ctx->'.$src.')';

        return 'phptal_exists($ctx, '.self::string(trim($src), $nothrow).')';
    }

    /**
     * number: modifier.
     *
     * Returns the number as is.
     */
    static public function number($src, $nothrow)
    {
        if (!is_numeric(trim($src))) throw new PHPTAL_ParserException("'$src' is not a number");
        return trim($src);
    }
    
    
    /**
     * translates TALES expression with alternatives into single PHP expression. 
     * Identical to compileExpressionToStatements() for singular expressions.
     *
     * @see PHPTAL_Php_TalesInternal::compileToPHPStatements()
     * @return string
    */
    public static function compileToPHPExpression($expression, $nothrow=false)
    {
        $r = self::compileToPHPStatements($expression, $nothrow);
        if (!is_array($r)) return $r;

        // this weird ternary operator construct is to execute noThrow inside the expression
        return '($ctx->noThrow(true)||1?'.self::convertStatementsToExpression($r, $nothrow).':"")';        
    }
    
    /*
     * helper function for compileExpressionToExpression
     * @access private
     */
    private static function convertStatementsToExpression(array $array, $nothrow)
    {
        if (count($array)==1) return '($ctx->noThrow('.($nothrow?'true':'false').')||1?('.
            ($array[0]==self::NOTHING_KEYWORD?'null':$array[0]).
            '):"")';

        $expr = array_shift($array);

        return "(!phptal_isempty(\$_tmp5=$expr) && (\$ctx->noThrow(false)||1)?\$_tmp5:".self::convertStatementsToExpression($array, $nothrow).')';
    }
    
    /**
     * returns PHP code that will evaluate given TALES expression.
     * e.g. "string:foo${bar}" may be transformed to "'foo'.phptal_escape($ctx->bar)"
     *
     * Expressions with alternatives ("foo | bar") will cause it to return array
     * Use PHPTAL_Php_TalesInternal::compileToPHPExpression() if you always want string.
     *
     * @param bool $nothrow if true, invalid expression will return NULL (at run time) rather than throwing exception
     * @return string or array
     */
    public static function compileToPHPStatements($expression,$nothrow=false)
    {
        $expression = trim($expression);

        // Look for tales modifier (string:, exists:, etc...)
        //if (preg_match('/^([-a-z]+):(.*?)$/', $expression, $m)) {
        if (preg_match('/^([a-z][.a-z_-]*[a-z]):(.*?)$/i', $expression, $m)) {
            list(,$typePrefix, $expression) = $m;
        }
        // may be a 'string'
        elseif (preg_match('/^\'((?:[^\']|\\\\.)*)\'$/', $expression, $m)) {
            $expression = stripslashes($m[1]);
            $typePrefix = 'string';
        }
        // failback to path:
        else {
            $typePrefix = 'path';
        }

        // is a registered TALES expression modifier
        if (PHPTAL_TalesRegistry::getInstance()->isRegistered($typePrefix)) {
            $callback = PHPTAL_TalesRegistry::getInstance()->getCallback($typePrefix);
            return call_user_func($callback, $expression, $nothrow);
        }

        // class method
        if (strpos($typePrefix, '.')) {
            $classCallback = explode('.', $typePrefix, 2);
            $callbackName  = null;
            if (!is_callable($classCallback, FALSE, $callbackName)) {
                throw new PHPTAL_ParserException("Unknown phptal modifier $typePrefix. Function $callbackName does not exists or is not statically callable");
            }
            $ref = new ReflectionClass($classCallback[0]);
            if (!$ref->implementsInterface('PHPTAL_Tales')) {
                throw new PHPTAL_ParserException("Unable to use phptal modifier $typePrefix as the class $callbackName does not implement the PHPTAL_Tales interface");
            }
            return call_user_func($classCallback, $expression, $nothrow);
        }

        // check if it is implemented via code-generating function
        $func = 'phptal_tales_'.str_replace('-','_', $typePrefix);
        if (function_exists($func)) {
            return $func($expression, $nothrow);
        }

        // check if it is implemented via runtime function
        $runfunc = 'phptal_runtime_tales_'.str_replace('-','_', $typePrefix);
        if (function_exists($runfunc)) {
            return "$runfunc(".self::compileToPHPExpression($expression, $nothrow).")";
        }

        throw new PHPTAL_ParserException("Unknown phptal modifier '$typePrefix'. Function '$func' does not exist");
    }
}


PHPTAL_Php_TalesInternal::registerInternalTales();
