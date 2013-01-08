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
 * @package PHPTAL
 * @subpackage Php
 */
class PHPTAL_Php_State
{
    private $debug      = false;
    private $tales_mode = 'tales';
    private $encoding;
    private $output_mode;
    private $phptal;

    public $_doctype,$_xmldeclaration;

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

    function __construct(PHPTAL $phptal)
    {
        $this->phptal = $phptal;
        $this->encoding = $phptal->getEncoding();
        $this->output_mode = $phptal->getOutputMode();
    }

    /**
     * used by codewriter to get information for phptal:cache
     */
    public function getCacheFilesBaseName()
    {
        return $this->phptal->getCodePath();
    }

    /**
     * true if PHPTAL has translator set
     */
    public function isTranslationOn()
    {
        return !!$this->phptal->getTranslator();
    }

    /**
     * controlled by phptal:debug
     */
    public function setDebug($bool)
    {
        $old = $this->debug;
        $this->debug = $bool;
        return $old;
    }

    /**
     * if true, add additional diagnostic information to generated code
     */
    public function isDebugOn()
    {
        return $this->debug;
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

    private $_functionPrefix = "";

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

    public function functionExists($name)
    {
        return isset($this->known_functions[$name]);
    }

    public function setFunctionExists($name)
    {
        $this->known_functions[$name] = true;
    }

    /**
     * Sets new and returns old TALES mode.
     * Valid modes are 'tales' and 'php'
     *
     * @param string $mode
     *
     * @return string
     */
    public function setTalesMode($mode)
    {
        $old = $this->tales_mode;
        $this->tales_mode = $mode;
        return $old;
    }

    public function getTalesMode()
    {
        return $this->tales_mode;
    }

    /**
     * encoding used for both template input and output
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Syntax rules to follow in generated code
     *
     * @return one of PHPTAL::XHTML, PHPTAL::XML, PHPTAL::HTML5
     */
    public function getOutputMode()
    {
        return $this->output_mode;
    }

    /**
     * Load prefilter
     */
    public function getPreFilterByName($name)
    {
        return $this->phptal->getPreFilterByName($name);
    }

    /**
     * compile TALES expression according to current talesMode
     * @return string with PHP code or array with expressions for TalesChainExecutor
     */
    public function evaluateExpression($expression)
    {
        if ($this->getTalesMode() === 'php') {
            return PHPTAL_Php_TalesInternal::php($expression);
        }
        return PHPTAL_Php_TalesInternal::compileToPHPExpressions($expression, false);
    }

    /**
     * compile TALES expression according to current talesMode
     * @return string with PHP code
     */
    private function compileTalesToPHPExpression($expression)
    {
        if ($this->getTalesMode() === 'php') {
            return PHPTAL_Php_TalesInternal::php($expression);
        }
        return PHPTAL_Php_TalesInternal::compileToPHPExpression($expression, false);
    }

    /**
     * returns PHP code that generates given string, including dynamic replacements
     *
     * It's almost unused.
     */
    public function interpolateTalesVarsInString($string)
    {
        return PHPTAL_Php_TalesInternal::parseString($string, false, ($this->getTalesMode() === 'tales') ? '' : 'php:' );
    }

    /**
     * replaces ${} in string, expecting HTML-encoded input and HTML-escapes output
     */
    public function interpolateTalesVarsInHTML($src)
    {
        return $this->interpolateTalesVars($src, 'html');
    }

    /**
     * replaces ${} in string, expecting CDATA (basically unescaped) input,
     * generates output protected against breaking out of CDATA in XML/HTML
     * (depending on current output mode).
     */
    public function interpolateTalesVarsInCDATA($src)
    {
        return $this->interpolateTalesVars($src, 'cdata');
    }

    public function interpolateTalesVars($src, $format)
    {
        $result = new PHPTAL_Expr_Append();

        $types = ini_get('short_open_tag')?'php|=|':'php';
        $parts = preg_split("/<\\?($types)(.*?)\\?>\n?/is", $src, NULL, PREG_SPLIT_DELIM_CAPTURE);

        foreach(array_chunk($parts, 3) as $php_part) {
            $parts = preg_split('/(?<!\$)((?:\$\$)*)\$\{(structure |text )?(.*?)\}/isS',$php_part[0], NULL, PREG_SPLIT_DELIM_CAPTURE);
            foreach(array_chunk($parts, 4) as $part) {

                // replace $${ with ${
                $text = preg_replace('/(\$+)\1(?={)/', '$1', $part[0]);

                if (isset($part[1])) {
                    // put back extra $$s before ${}
                    $text .= substr($part[1], strlen($part[1])/2);
                }

                if ($text !== '') {
                    $result->append(new PHPTAL_Expr_String($text));
                }

                if (isset($part[3])) {
                    $code = $part[3];
                    if ($format == 'html') $code = html_entity_decode($code, ENT_QUOTES, $this->getEncoding());
                    $code = $this->compileTalesToPHPExpression($code);
                    $result->append($this->escapeCode($code, $part[2], $format));
                }
            }

             if (isset($php_part[2])) {
                 $code = rtrim($php_part[2],';');
                 if ($php_part[1]=='=') {
                     $result->append(new PHPTAL_Expr_PHP($code));
                 } else {
                     $result->append(PHPTAL_Php_TalesInternal::phptal_internal_php_block($code));
                 }
             }
        }
        return $result->optimized();
    }

    private function escapeCode(PHPTAL_Expr $code, $structure, $format)
    {
        if (rtrim($structure) == 'structure') { // regex captures a space there
            return new PHPTAL_Expr_Stringify($code);
        }
        if ($format == 'html') {
            return new PHPTAL_Expr_Escape($code);
        }
        if ($format == 'cdata') {
            // quite complex for an "unescaped" section, isn't it?
            if ($this->getOutputMode() === PHPTAL::HTML5) {
                return new PHPTAL_Expr_PHP("str_replace('</','<\\\\/', ",new PHPTAL_Expr_Stringify($code),")");
            } elseif ($this->getOutputMode() === PHPTAL::XHTML) {
                // both XML and HMTL, because people will inevitably send it as text/html :(
                return new PHPTAL_Expr_PHP("strtr(",new PHPTAL_Expr_Stringify($code)," ,array(']]>'=>']]]]><![CDATA[>','</'=>'<\\/'))");
            } else {
                return new PHPTAL_Expr_PHP("str_replace(']]>',']]]]><![CDATA[>', ",new PHPTAL_Expr_Stringify($code),")");
            }
        }
        assert(0);
    }
}

