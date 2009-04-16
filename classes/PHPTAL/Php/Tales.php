<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.motion-twin.com/ 
 */
define('PHPTAL_TALES_DEFAULT_KEYWORD', '_DEFAULT_DEFAULT_DEFAULT_DEFAULT_');
define('PHPTAL_TALES_NOTHING_KEYWORD', '_NOTHING_NOTHING_NOTHING_NOTHING_');


/**
 * TALES Specification 1.3
 *
 *      Expression  ::= [type_prefix ':'] String
 *      type_prefix ::= Name
 *
 * Examples:
 *
 *      a/b/c
 *      path:a/b/c
 *      nothing
 *      path:nothing
 *      python: 1 + 2
 *      string:Hello, ${username}
 *
 *
 * Builtin Names in Page Templates (for PHPTAL)
 *
 *      * nothing - special singleton value used by TAL to represent a 
 *        non-value (e.g. void, None, Nil, NULL).
 *        
 *      * default - special singleton value used by TAL to specify that 
 *        existing text should not be replaced.
 *
 *      * repeat - the repeat variables (see RepeatVariable).
 * 
 *
 *
 *  helper function for phptal_tale
 * @access private
 */
function _phptal_tale_wrap($array, $nothrow)
{
	if (count($array)==1) return '($ctx->noThrow('.($nothrow?'true':'false').')||1?('.
		($array[0]==PHPTAL_TALES_NOTHING_KEYWORD?'null':$array[0]).
		'):"")';
	
	$expr = array_shift($array);
	
	return "(!phptal_isempty(\$_tmp5=$expr) && (\$ctx->noThrow(false)||1)?\$_tmp5:"._phptal_tale_wrap($array, $nothrow).')';
}

/** 
 * translates array of alternative expressions into single PHP expression. Identical to phptal_tales() for singular expressions. 
 * @see phptal_tales()
 * @return string
*/
function phptal_tale($expression, $nothrow=false)
{
	$r = phptal_tales($expression,$nothrow);
	if (!is_array($r)) return $r;
	
	// this weird ternary operator construct is to execute noThrow inside the expression
	return '($ctx->noThrow(true)||1?'._phptal_tale_wrap($r, $nothrow).':"")';
}

/**
 * returns PHP code that will evaluate given TALES expression.
 * e.g. "string:foo${bar}" may be transformed to "'foo'.phptal_escape($ctx->bar)"
 * 
 * Expressions with alternatives ("foo | bar") will cause it to return array
 * Use phptal_tale() if you always want string.
 *
 * @param bool $nothrow if true, invalid expression will return NULL (at run time) rather than throwing exception
 * @return string or array
 */
function phptal_tales($expression, $nothrow=false)
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
        return "$runfunc(".phptal_tale($expression, $nothrow).")";
    }
    
    throw new PHPTAL_ParserException("Unknown phptal modifier '$typePrefix'. Function '$func' does not exist");
}


// Register internal Tales expression modifiers
require PHPTAL_DIR.'PHPTAL/TalesRegistry.php';
require PHPTAL_DIR.'PHPTAL/Php/TalesInternal.php';
