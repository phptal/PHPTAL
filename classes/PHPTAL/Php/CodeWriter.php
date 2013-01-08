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
 * @link     http://phptal.org/
 */
/**
 * Helps generate php representation of a template.
 *
 * @package PHPTAL
 * @subpackage Php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_CodeWriter
{
    /**
     * max id of variable to give as temp
     */
    private $temp_var_counter=0;
    /**
     * stack with free'd variables
     */
    private $temp_recycling=array();

    /**
     * keeps track of seen functions for function_exists
     */
    private $known_functions = array();


    public function __construct(PHPTAL_Php_State $state)
    {
        $this->_state = $state;
    }

    public function createTempVariable()
    {
        if (count($this->temp_recycling)) return array_shift($this->temp_recycling);
        return new PHPTAL_Expr_TempVar('$_tmp_'.(++$this->temp_var_counter));
    }

    public function recycleTempVariable(PHPTAL_Expr_TempVar $var)
    {
        $this->temp_recycling[] = $var;
    }

    public function getCacheFilesBaseName()
    {
        return $this->_state->getCacheFilesBaseName();
    }

    public function getResult()
    {
        $this->flush();
        if (version_compare(PHP_VERSION, '5.3', '>=') && __NAMESPACE__) {
            return '<?php use '.'PHPTALNAMESPACE as P; ?>'.trim($this->_result);
        } else {
            return trim($this->_result);
        }
    }

    /**
     * set full '<!DOCTYPE...>' string to output later
     *
     * @param string $dt
     *
     * @return void
     */
    public function setDocType($dt)
    {
        $this->_doctype = $dt;
    }

    /**
     * set full '<?xml ?>' string to output later
     *
     * @param string $dt
     *
     * @return void
     */
    public function setXmlDeclaration($dt)
    {
        $this->_xmldeclaration = $dt;
    }

    /**
     * functions later generated and checked for existence will have this prefix added
     * (poor man's namespace)
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setFunctionPrefix($prefix)
    {
        $this->_functionPrefix = $prefix;
    }

    /**
     * @return string
     */
    public function getFunctionPrefix()
    {
        return $this->_functionPrefix;
    }

    /**
     * @see PHPTAL_Php_State::setTalesMode()
     *
     * @param string $mode
     *
     * @return string
     */
    public function setTalesMode($mode)
    {
        return $this->_state->setTalesMode($mode);
    }

    public function splitExpression($src)
    {
        preg_match_all('/(?:[^;]+|;;)+/sm', $src, $array);
        $array = $array[0];
        foreach ($array as &$a) $a = str_replace(';;', ';', $a);
        return $array;
    }

    public function evaluateExpression($src)
    {
        return $this->_state->evaluateExpression($src);
    }

    public function indent()
    {
        $this->_indentation ++;
    }

    public function unindent()
    {
        $this->_indentation --;
    }

    public function flush()
    {
        $this->flushCode();
        $this->flushHtml();
    }

    public function noThrow($bool)
    {
        if ($bool) {
            $this->pushCode(new PHPTAL_Expr_PHP('$ctx->noThrow(true)'));
        } else {
            $this->pushCode(new PHPTAL_Expr_PHP('$ctx->noThrow(false)'));
        }
    }

    private function isExpressionEchoOfStrings(PHPTAL_Expr $e)
    {
        if (!$e instanceof PHPTAL_Expr_Echo) return false;

        foreach($e->subexpressions as $subexpr) {
            if (!$subexpr instanceof PHPTAL_Expr_String) return false;
        }

        return true;
    }

    public function flushCode()
    {
        if (count($this->_codeBuffer) == 0) return;

        // change echo to HTML from beginning of code
        foreach($this->_codeBuffer as $k => $codeLine) {
            if (!$this->isExpressionEchoOfStrings($codeLine[1])) break;

            foreach($codeLine[1]->subexpressions as $subexpr) {
                $this->_result .= $subexpr->getStringValue();
            }

            unset($this->_codeBuffer[$k]);
        }

        // change echo to HTML from end of code
        $end_result = '';
        foreach(array_reverse($this->_codeBuffer,true) as $k => $codeLine) {
            if (!$this->isExpressionEchoOfStrings($codeLine[1])) break;

            foreach($codeLine[1]->subexpressions as $subexpr) {
                $end_result = $subexpr->getStringValue() . $end_result;
            }

            unset($this->_codeBuffer[$k]);
        }

        // output remaining code
        $nl = count($this->_codeBuffer)==1 ? " " : "\n";

        $this->_result .= '<?php'.$nl;
        foreach ($this->_codeBuffer as $codeLine) {
            $codeLine = $codeLine[0] . $codeLine[1];
            // avoid adding ; after } and {
            if (!preg_match('/[{};]\s*$/', $codeLine)) {
                $codeLine .= ';'.$nl;
            }
            $this->_result .= $codeLine;
        }
        $this->_result .= "?>\n" . $end_result;// PHP consumes newline
        $this->_codeBuffer = array();
    }

    public function flushHtml()
    {
        if (count($this->_htmlBuffer) == 0) return;

        $this->_result .= implode('', $this->_htmlBuffer);
        $this->_htmlBuffer = array();
    }

    /**
     * Generate code for setting DOCTYPE
     *
     * @param bool $called_from_macro for error checking: unbuffered output doesn't support that
     */
    public function doDoctype($called_from_macro = false)
    {
        if ($this->_doctype) {
            $code = new PHPTAL_Expr_PHP('$ctx->setDocType(',new PHPTAL_Expr_String($this->_doctype),',',
                ($called_from_macro?'true':'false'),')');
            $this->pushCode($code);
        }
    }

    /**
     * Generate XML declaration
     *
     * @param bool $called_from_macro for error checking: unbuffered output doesn't support that
     */
    public function doXmlDeclaration($called_from_macro = false)
    {
        if ($this->_xmldeclaration && $this->getOutputMode() !== PHPTAL::HTML5) {
            $code = new PHPTAL_Expr_PHP('$ctx->setXmlDeclaration(',new PHPTAL_Expr_String($this->_xmldeclaration),',',
                ($called_from_macro?'true':'false').')');
            $this->pushCode($code);
        }
    }

    public function functionExists($name)
    {
        return isset($this->known_functions[$this->_functionPrefix . $name]);
    }

    public function doTemplateFile($functionName, PHPTAL_Dom_Element $treeGen)
    {
        $this->doComment("\n*** DO NOT EDIT THIS FILE ***\n\nGenerated by PHPTAL from ".$treeGen->getSourceFile()." (edit that file instead)");
        $this->doFunction($functionName, 'PHPTAL $tpl, PHPTAL_Context $ctx');
        $this->setFunctionPrefix($functionName . "_");
        $this->doSetVar('$_thistpl', new PHPTAL_Expr_PHP('$tpl'));
        $this->doInitTranslator();
        $treeGen->generateCode($this);
        $this->doComment("end");
        $this->doEnd('function');
    }

    public function doFunction($name, $params)
    {
        $name = $this->_functionPrefix . $name;
        $this->known_functions[$name] = true;

        $this->pushCodeWriterContext();
        $this->pushCode(new PHPTAL_Expr_PHP("function $name($params) {\n"));
        $this->indent();
        $this->_segments[] =  'function';
    }

    public function doComment($comment)
    {
        $comment = str_replace('*/', '* /', $comment);
        $this->pushCode(new PHPTAL_Expr_PHP("/* $comment */"));
    }

    public function doInitTranslator()
    {
        if ($this->_state->isTranslationOn()) {
            $this->doSetVar('$_translator', '$tpl->getTranslator()');
        }
    }

    public function getTranslatorReference()
    {
        if (!$this->_state->isTranslationOn()) {
            throw new PHPTAL_ConfigurationException("i18n used, but Translator has not been set");
        }
        return '$_translator';
    }

    public function doEval(PHPTAL_Expr $code)
    {
        $this->pushCode($code);
    }

    public function doForeach($out, PHPTAL_Expr $source)
    {
        $this->_segments[] =  'foreach';
        $this->pushCode(new PHPTAL_Expr_PHP("foreach (",$source," as ",$out,"):"));
        $this->indent();
    }

    public function doEnd($expects = null)
    {
        if (!count($this->_segments)) {
            if (!$expects) $expects = 'anything';
            throw new PHPTAL_Exception("Bug: CodeWriter generated end of block without $expects open");
        }

        $segment = array_pop($this->_segments);
        if ($expects !== null && $segment !== $expects) {
            throw new PHPTAL_Exception("Bug: CodeWriter generated end of $expects, but needs to close $segment");
        }

        $this->unindent();
        if ($segment == 'function') {
            $this->pushCode(new PHPTAL_Expr_PHP("\n}\n\n"));
            $this->flush();
            $functionCode = $this->_result;
            $this->popCodeWriterContext();
            $this->_result = $functionCode . $this->_result;
        } elseif ($segment == 'try')
            $this->pushCode(new PHPTAL_Expr_PHP('}'));
        elseif ($segment == 'catch')
            $this->pushCode(new PHPTAL_Expr_PHP('}'));
        else
            $this->pushCode(new PHPTAL_Expr_PHP("end$segment"));
    }

    public function doTry()
    {
        $this->_segments[] =  'try';
        $this->pushCode(new PHPTAL_Expr_PHP('try {'));
        $this->indent();
    }

    public function doSetVar($varname, $code)
    {
        $this->pushCode(new PHPTAL_Expr_PHP($varname,' = ',$code));
    }

    public function doCatch($exception,$var)
    {
        $this->doEnd('try');
        $this->_segments[] =  'catch';
        $this->pushCode(new PHPTAL_Expr_PHP('catch(',$exception,' ',$var,') {'));
        $this->indent();
    }

    public function doIf(PHPTAL_Expr $condition)
    {
        $this->_segments[] =  'if';
        $this->pushCode(new PHPTAL_Expr_PHP('if (',$condition,'): '));
        $this->indent();
    }

    public function doElseIf(PHPTAL_Expr $condition)
    {
        if (end($this->_segments) !== 'if') {
            throw new PHPTAL_Exception("Bug: CodeWriter generated elseif without if");
        }
        $this->unindent();
        $this->pushCode(new PHPTAL_Expr_PHP('elseif (',$condition,'): '));
        $this->indent();
    }

    public function doElse()
    {
        if (end($this->_segments) !== 'if') {
            throw new PHPTAL_Exception("Bug: CodeWriter generated else without if");
        }
        $this->unindent();
        $this->pushCode(new PHPTAL_Expr_PHP('else: '));
        $this->indent();
    }

    public function doEcho(PHPTAL_Expr $code)
    {
        if ($code === "''") return;
        $this->flush();
        $this->pushCode(new PHPTAL_Expr_Echo(new PHPTAL_Expr_Escape($code)));
    }

    public function doEchoRaw(PHPTAL_Expr $code)
    {
        if ($code === "''") return;
        if (is_string($code)) $code = new PHPTAL_Expr_PHP($code);
        $this->pushCode(new PHPTAL_Expr_Echo(new PHPTAL_Expr_Stringify($code)));
    }

    public function interpolateHTML($html)
    {
        return $this->_state->interpolateTalesVarsInHTML($html);
    }

    public function interpolateCDATA($str)
    {
        assert('is_string($str)');
        return $this->_state->interpolateTalesVarsInCDATA($str);
    }

    public function pushHTML($html)
    {
        assert('is_string($html)');
        if ($html === "") return;
        $this->flushCode();
        $this->_htmlBuffer[] =  $html;
    }

    public function pushCode(PHPTAL_Expr $codeLine)
    {
        $this->flushHtml();
        $this->_codeBuffer[] =array($this->indentSpaces(), $codeLine->optimized());
    }

    public function getEncoding()
    {
        return $this->_state->getEncoding();
    }

    public function interpolateTalesVarsInString($src)
    {
        assert('is_string($src)');
        return $this->_state->interpolateTalesVarsInString($src);
    }

    public function setDebug($bool)
    {
        return $this->_state->setDebug($bool);
    }

    public function isDebugOn()
    {
        return $this->_state->isDebugOn();
    }

    public function getOutputMode()
    {
        return $this->_state->getOutputMode();
    }

    public function quoteAttributeValue(PHPTAL_Expr $value)
    {
        $value = $value->optimized();

        if ($this->isUnquotedStringSafe($value)) return $value;

        return new PHPTAL_Expr_Append(
            new PHPTAL_Expr_String('"'),
            $value,
            new PHPTAL_Expr_String('"'));

    }

    private function isUnquotedStringSafe(PHPTAL_Expr $value)
    {
        if ($value instanceof PHPTAL_Expr_String) {
        if ($this->getEncoding() == 'UTF-8') // HTML 5: 8.1.2.3 Attributes ; http://code.google.com/p/html5lib/issues/detail?id=93
        {
            // regex excludes unicode control characters, all kinds of whitespace and unsafe characters
            // and trailing / to avoid confusion with self-closing syntax
            $unsafe_attr_regex = '/^$|[&=\'"><\s`\pM\pC\pZ\p{Pc}\p{Sk}]|\/$/u';
        } else {
            $unsafe_attr_regex = '/^$|[&=\'"><\s`\0177-\377]|\/$/';
        }

            return $this->getOutputMode() == PHPTAL::HTML5 && !preg_match($unsafe_attr_regex, $value->getStringValue());

        }
        return false;
    }

    public function pushContext()
    {
        $this->doSetVar('$ctx', new PHPTAL_Expr_PHP('$tpl->pushContext()'));
    }

    public function popContext()
    {
        $this->doSetVar('$ctx', new PHPTAL_Expr_PHP('$tpl->popContext()'));
    }

    // ~~~~~ Private members ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private function indentSpaces()
    {
        return str_repeat("\t", $this->_indent);
    }

    private function pushCodeWriterContext()
    {
        $this->_contexts[] =  clone $this;
        $this->_result = "";
        $this->_indent = 0;
        $this->_codeBuffer = array();
        $this->_htmlBuffer = array();
        $this->_segments = array();
    }

    private function popCodeWriterContext()
    {
        $oldContext = array_pop($this->_contexts);
        $this->_result = $oldContext->_result;
        $this->_indent = $oldContext->_indent;
        $this->_codeBuffer = $oldContext->_codeBuffer;
        $this->_htmlBuffer = $oldContext->_htmlBuffer;
        $this->_segments = $oldContext->_segments;
    }

    private $_state;
    private $_result = "";
    private $_indent = 0;
    private $_codeBuffer = array();
    private $_htmlBuffer = array();
    private $_segments = array();
    private $_contexts = array();
    private $_functionPrefix = "";
    private $_doctype = "";
    private $_xmldeclaration = "";
}

