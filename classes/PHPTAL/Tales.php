<?php
/**
 * @package PHPTAL
 */

define('PHPTAL_TALES_DEFAULT_KEYWORD', '_DEFAULT_DEFAULT_DEFAULT_DEFAULT_');
define('PHPTAL_TALES_NOTHING_KEYWORD', '_NOTHING_NOTHING_NOTHING_NOTHING_');


// TALES Specification 1.3
//
//      Expression  ::= [type_prefix ':'] String
//      type_prefix ::= Name
//
// Examples:
//
//      a/b/c
//      path:a/b/c
//      nothing
//      path:nothing
//      python: 1 + 2
//      string:Hello, ${username}
//
//
// Builtin Names in Page Templates (for PHPTAL)
//
//      * nothing - special singleton value used by TAL to represent a 
//        non-value (e.g. void, None, Nil, NULL).
//        
//      * default - special singleton value used by TAL to specify that 
//        existing text should not be replaced.
//
//      * repeat - the repeat variables (see RepeatVariable).
// 
function phptal_tales( $expression, $nothrow=false )
{
    $expression = trim($expression);
    if (preg_match('/^([-a-z]+):(.*?)$/', $expression, $m)) {
        list(,$typePrefix,$expression) = $m;
    }
    else if (preg_match('/^\'(.*?)\'$/', $expression, $m)) {
        list(,$expression) = $m;
        $typePrefix = 'string';
    }
    else {
        $typePrefix = 'path';
    }
    $func = 'phptal_tales_'.str_replace('-','_',$typePrefix);
    return $func($expression, $nothrow);
}


// 
// not: 
//
//      not: Expression
//
// evaluate the expression string (recursively) as a full expression, 
// and returns the boolean negation of its value
// 
// return boolean based on the following rules:
// 
//     1. integer 0 is false  
//     2. integer > 0 is true  
//     3. an empty string or other sequence is false  
//     4. a non-empty string or other sequence is true  
//     5. a non-value (e.g. void, None, Nil, NULL, etc) is false  
//     6. all other values are implementation-dependent.
//
// Examples:
//  
//      not: exists: foo/bar/baz
//      not: php: object.hasChildren()
//      not: string:${foo}
//      not: foo/bar/booleancomparable
// 
function phptal_tales_not( $expression, $nothrow )
{
    return '!' . phptal_tales($expression, $nothrow);
}

// 
// path:
//         
//      PathExpr  ::= Path [ '|' Path ]*
//      Path      ::= variable [ '/' URL_Segment ]*
//      variable  ::= Name
//
// Examples:
//
//      path: username
//      path: user/name
//      path: object/method/10/method/member
//      path: object/${dynamicmembername}/method
//      path: maybethis | path: maybethat | path: default
//
// PHPTAL: 
//
// 'default' may lead to some 'difficult' attributes implementation
//
// For example, the tal:content will have to insert php code like:
//
// if (isset($tpl->maybethis)) {
//     echo $tpl->maybethis;
// }
// else if (isset($tpl->maybethat) {
//     echo $tpl->maybethat;
// }
// else {
//     // process default tag content
// }
//
// @returns string or array
// 
function phptal_tales_path( $expression, $nothrow=false )
{
    if ($expression == 'default') return PHPTAL_TALES_DEFAULT_KEYWORD;
    if ($expression == 'nothing') return PHPTAL_TALES_NOTHING_KEYWORD;
    if ($expression == '') return PHPTAL_TALES_NOTHING_KEYWORD;
  

    if (preg_match('/^(.*?)\s*?\|\s*?(string:.*?)$/sm', $expression, $m)){
        list(, $expression, $string) = $m;
    }
    else if (preg_match('/^(.*?)\s*?\|\s*?(\'.*?\')$/sm', $expression, $m)){
        list(, $expression, $string) = $m;
        $string = 'string:'.substr($string, 1, -1);
    }
        
    $exps = preg_split('/\s*?\|\s*?/sm', $expression);
    if (count($exps) > 1 || isset($string)) {
        $result = array();
        foreach ($exps as $exp) {
            array_push($result, phptal_tales(trim($exp), true));
        }
        if (isset($string)){
            array_push($result, phptal_tales($string), true);
        }
        return $result;
    }
    
    if ($nothrow) 
        return 'phptal_path($tpl, \''.$expression.'\', true)';

    return 'phptal_path($tpl, \''.$expression.'\')';
}

//      
// string:
//
//      string_expression ::= ( plain_string | [ varsub ] )*
//      varsub            ::= ( '$' Path ) | ( '${' Path '}' )
//      plain_string      ::= ( '$$' | non_dollar )*
//      non_dollar        ::= any character except '$'
//
// Examples:
//
//      string:my string
//      string:hello, $username how are you
//      string:hello, ${user/name}
//      string:you have $$130 in your bank account
//
function phptal_tales_string( $expression )
{
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
                }
                else {
                    $lastWasDollar = true;
                    $c = '';
                }
                break;

            case '{':
                if ($lastWasDollar) {
                    $inAccoladePath = true;
                    $subPath = $c;
                    $c = '';
                }
                break;

            case '}':
                if ($inAccoladePath) {
                    $inAccoladePath = false;
                    $subEval = phptal_tales_path($subPath);
                    if (is_array($subEval)) {
                        $err = 'cannot use | operator is evaluated expressions';
                        throw new Exception($err);
                    }
                    $result .= "'." . $subEval . ".'";
                    $c = '';
                }
                break;

            default:
                if ($lastWasDollar) {
                    $lastWasDollar = false;
                    $inPath = true;
                    $subPath = $c;
                    $c = '';
                }
                else if ($inAccoladePath) {
                    $subPath .= $c;
                    $c = '';
                }
                else if ($inPath) {
                    $t = strtolower($c);
                    if (($t >= 'a' && $t <= 'z') || ($t >= '0' && $t <= '9') || ($t == '_')){
                        $subPath .= $c;
                        $c = '';
                    }
                    else {
                        $inPath = false;
                        $subEval = phptal_tales_path($subPath);
                        if (is_array($subEval)) {
                            $err = 'cannot use | operator is evaluated expressions';
                            throw new Exception($err);
                        }
                        $result .= "'." . $subEval . ".'";
                    }
                }
                break;
        }
        $result .= $c;        
    }
    return "'$result'";
}


function phptal_tales_php( $src )
{
    require_once 'PHPTAL/PhpTransformer.php';
    $trans = new PHPTAL_PhpTransformer();
    $trans->prefix = '$tpl->';
    return $trans->transform($src);
}


function phptal_tales_exists( $src )
{
    return phptal_tales($src, true) . ' != null';
}

?>
