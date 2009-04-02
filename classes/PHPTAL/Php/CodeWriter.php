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
/**
 * Helps generate php representation of a template.
 *
 * @package PHPTAL.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_CodeWriter
{
    public function __construct(PHPTAL_Php_State $state)
    {
        $this->_state = $state;
    }

    private $temp_var_counter=0;
    private $temp_recycling=array();
    public function createTempVariable()
    {
        if (count($this->temp_recycling)) return array_shift($this->temp_recycling);
        return '$_tmp_'.(++$this->temp_var_counter);
    }
    
    public function recycleTempVariable($var)
    {
        assert('substr($var,0,6)===\'$_tmp_\'');
        $this->temp_recycling[] = $var;
    }

    public function getCacheFilesBaseName()
    {
        return $this->_state->getCacheFilesBaseName();
    }

    public function getResult()
    {
        $this->flush();
        $this->_result = trim($this->_result);
        return $this->_result;
    }

    public function setDocType($dt)
    {
        assert('is_string($dt)');
        $this->_doctype = $dt;
    }

    public function setXmlDeclaration($dt)
    {
        assert('is_string($dt)');
        $this->_xmldeclaration = $dt;
    }

    public function setFunctionPrefix($prefix)
    {
        $this->_functionPrefix = $prefix;
    }

    public function getFunctionPrefix()
    {
        return $this->_functionPrefix;
    }

    /**
     * Returns old tales mode.
     */
    public function setTalesMode($mode)
    {
        return $this->_state->setTalesMode($mode);
    }

    public function splitExpression($src)
    {
        preg_match_all('/(?:[^;]+|;;)+/sm', $src, $array);
        $array = $array[0];
        foreach ($array as &$a) $a = str_replace(';;',';', $a);
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
            $this->pushCode('$ctx->noThrow(true)');
        } else {
            $this->pushCode('$ctx->noThrow(false)');
        }
    }
    
    public function flushCode()
    {
        if (count($this->_codeBuffer) == 0) 
            return;

        // special treatment for one code line
        if (count($this->_codeBuffer) == 1) {
            $codeLine = $this->_codeBuffer[0];
            // avoid adding ; after } and {
            if (!preg_match('/\}|\{\s+$/', $codeLine))
                $this->_result .= '<?php '.$codeLine.'; ?>';
            else
                $this->_result .= '<?php '.$codeLine.' ?>';
            $this->_codeBuffer = array();
            return;
        }
    
        $this->_result .= '<?php '."\n";
        foreach ($this->_codeBuffer as $codeLine) {
            // avoid adding ; after } and {
            if (!preg_match('/\}|\{\s+$/', $codeLine))
                $this->_result .= $codeLine . ' ;'."\n";
            else 
                $this->_result .= $codeLine;
        }
        $this->_result .= '?>';
        $this->_codeBuffer = array();
    }
    
    public function flushHtml()
    {
        if (count($this->_htmlBuffer) == 0) return;
        
        $this->_result .= join( '', $this->_htmlBuffer );
        $this->_htmlBuffer = array();
    }

    public function doDoctype()
    {
        if ($this->_doctype) {
            $code = '$ctx->setDocType('.$this->str($this->_doctype).')';
            $this->pushCode($code);
        }
    }

    public function doXmlDeclaration()
    {
        if ($this->_xmldeclaration) {
            $code = '$ctx->setXmlDeclaration('.$this->str($this->_xmldeclaration).')';
            $this->pushCode($code);
        }
    }

    public function functionExists($name)
    {
        return isset($this->known_functions[$this->_functionPrefix . $name]);
    }

    private $known_functions = array();
    
    public function doFunction($name, $params)
    {
        $name = $this->_functionPrefix . $name;
        $this->known_functions[$name] = true;
                
        $this->pushCodeWriterContext();
        $this->pushCode("function $name( $params ) {\n");
        $this->indent();
        $this->_segments[] =  'function';
    }
    
    public function doComment($comment)
    {
        $comment = str_replace('*/', '* /', $comment);
        $this->pushCode("/* $comment */");
    }

    public function doEval($code)
    {
        $this->pushCode($code);
    }
                       
    public function doForeach($out, $source)
    {
        $this->_segments[] =  'foreach';
        $this->pushCode("foreach ($source as $out):");
        $this->indent();
    }

    public function doEnd()
    {
        $segment = array_pop($this->_segments);
        $this->unindent();
        if ($segment == 'function') {
            $this->pushCode("\n}\n\n");
            $functionCode = $this->getResult();
            $this->popCodeWriterContext();
            $this->_result = $functionCode . $this->_result;
        } elseif ($segment == 'try')
            $this->pushCode('}');
        elseif ($segment == 'catch')
            $this->pushCode('}');
        else 
            $this->pushCode("end$segment");
    }

    public function doTry()
    {
        $this->_segments[] =  'try';
        $this->pushCode('try {');
        $this->indent();
    }

    public function doSetVar($varname, $code)
    {
        $this->pushCode($varname.' = '.$code);
    }
        
    public function doCatch($catch)
    {
        $this->doEnd();
        $this->_segments[] =  'catch';
        $this->pushCode('catch('.$catch.') {');
        $this->indent();
    }

    public function doIf($condition)
    {
        $this->_segments[] =  'if';
        $this->pushCode('if ('.$condition.'): ');
        $this->indent();
    }

    public function doElseIf($condition)
    {
        $this->unindent();
        $this->pushCode('elseif ('.$condition.'): ');
        $this->indent();
    }

    public function doElse()
    {
        $this->unindent();
        $this->pushCode('else: ');
        $this->indent();
    }

    public function doEcho($code)
    {
        $this->flush();
        $this->pushRawHtml('<?php echo '.$this->escapeCode($code).' ?>');
    }

    public function doEchoRaw($code)
    {
        $this->pushHtml('<?php echo '.$code.' ?>');
    }

    public function pushHtml($html)
    {
        $html = $this->_state->interpolateTalesVarsInHtml($html);
        $this->flushCode();
        $this->_htmlBuffer[] =  $html;
    }

    public function pushCDATA($html)
    {
        $html = $this->_state->interpolateTalesVarsInCDATA($html);
        $this->flushCode();
        $this->_htmlBuffer[] =  $html;
    }

	public function pushRawHtml($html)
	{
    	$this->flushCode();
		$this->_htmlBuffer[] =  $html;
	}

    public function pushCode($codeLine) 
    {
        $this->flushHtml();
        $codeLine = $this->indentSpaces() . $codeLine;
        $this->_codeBuffer[] =  $codeLine;
    }
    
    /**
     * php string with escaped text
     */
    public function str($string)
    {
        return '\''.str_replace('\'', '\\\'', $string).'\'';
    }

    public function escapeCode($code)
    {
        return $this->_state->htmlchars($code);
    }
    
    public function getEncoding()
    {
        return $this->_state->getEncoding();
    }

    public function interpolateTalesVarsInString($src)
    {
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

    public function quoteAttributeValue($value)
    {
        if ($this->getEncoding() == 'UTF-8') // HTML 5: 8.1.2.3 Attributes ; http://code.google.com/p/html5lib/issues/detail?id=93
        {
            $attr_regex = '/^[^$&\/=\'"><\s`\pM\pC\pZ\p{Pc}\p{Sk}]+$/u'; // FIXME: interpolation is done _after_ that function, so $ must be forbidden for now
        } else {
            $attr_regex = '/^[^$&\/=\'"><\s`\0177-\377]+$/';
        }
        
        if ($this->getOutputMode() == PHPTAL::HTML5 && preg_match($attr_regex, $value)) 
            return $value;
        else return '"'.$value.'"';
    }

    public function pushContext()
    {
        $this->pushCode('$ctx = $tpl->pushContext()');
    }

    public function popContext()
    {
        $this->pushCode('$ctx = $tpl->popContext()');
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


