<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

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
function phptal_tales($expression, $nothrow=false)
{
    $expression = trim($expression);

    // Look for tales modifier (string:, exists:, etc...)
    if (preg_match('/^([-a-z]+):(.*?)$/', $expression, $m)) {
        list(,$typePrefix,$expression) = $m;
    }
    // may be a 'string'
    else if (preg_match('/^\'(.*?)\'$/', $expression, $m)) {
        list(,$expression) = $m;
        $typePrefix = 'string';
    }
    // failback to path:
    else {
        $typePrefix = 'path';
    }
   
    // call matching modified function
    $func = 'phptal_tales_'.str_replace('-','_',$typePrefix);
    if (!function_exists($func)){
        $err = 'Unknown phptal modifier %s function %s does not exists';
        $err = sprintf($err, $typePrefix, $func);
        throw new Exception($err);
    }
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
function phptal_tales_not($expression, $nothrow)
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
// if (isset($ctx->maybethis)) {
//     echo $ctx->maybethis;
// }
// else if (isset($ctx->maybethat) {
//     echo $ctx->maybethat;
// }
// else {
//     // process default tag content
// }
//
// @returns string or array
// 
function phptal_tales_path($expression, $nothrow=false)
{
    $expression = trim($expression);
    if ($expression == 'default') return PHPTAL_TALES_DEFAULT_KEYWORD;
    if ($expression == 'nothing') return PHPTAL_TALES_NOTHING_KEYWORD;
    if ($expression == '')        return PHPTAL_TALES_NOTHING_KEYWORD;
  
    // split OR expressions terminated by a string
    if (preg_match('/^(.*?)\s*?\|\s*?(string:.*?)$/sm', $expression, $m)){
        list(, $expression, $string) = $m;
    }
    // split OR expressions terminated by a 'fast' string
    else if (preg_match('/^(.*?)\s*?\|\s*?(\'.*?\')$/sm', $expression, $m)){
        list(, $expression, $string) = $m;
        $string = 'string:'.substr($string, 1, -1);
    }

    // split OR expressions
    $exps = preg_split('/\s*?\|\s*?/sm', $expression);

    // if (many expressions) or (expressions or terminating string) found then
    // generate the array of sub expressions and return it.
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

    // only one expression to process

    // first evaluate ${foo} inside the expression and threat the expression
    // as if it was a string to interpolate
    $expression = phptal_tales_string($expression);

    // if no sub part for this expression, just optimize the generated code
    // and access the $ctx->var
    if (strpos($expression, '/') === false) {
        return '$ctx->'. substr($expression, 1, -1);
    }

    // otherwise we have to call phptal_path() to resolve the path at runtime
    // extract the first part of the expression (it will be the phptal_path()
    // $base and pass the remaining of the path to phptal_path()
    $parts = split('/', substr($expression, 1, -1));
    $next = array_shift($parts);
    $expression = '\''. join('/', $parts) . '\'';

    // return php code invoking phptal_path()
    if ($nothrow) 
        return 'phptal_path($ctx->'.$next.', '.$expression.', true)';
    return 'phptal_path($ctx->'.$next.', '.$expression.')';
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
function phptal_tales_string( $expression, $nothrow=false )
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
                }
                else {
                    $lastWasDollar = true;
                    $c = '';
                }
                break;

            case '{':
                if ($lastWasDollar) {
                    $lastWasDollar = false;
                    $inAccoladePath = true;
                    $subPath = '';
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
    return '\''.$result.'\'';
}


/** 
 * php: modifier.
 *
 * Transform the expression into a regular PHP expression.
 */
function phptal_tales_php($src)
{
    require_once 'PHPTAL/PhpTransformer.php';
    return PHPTAL_PhpTransformer::transform($src, '$ctx->');
}

/**
 * exists: modifier.
 *
 * Returns the code required to invoke phptal_exists() on specified path.
 */
function phptal_tales_exists($src, $nothrow)
{
    return sprintf('phptal_exists($ctx, %s)',
                   phptal_tales_string(trim($src), $nothrow));
}

/**
 * number: modifier.
 *
 * Returns the number as is.
 */
function phptal_tales_number($src, $nothrow)
{
    return trim($src);
}

?>
