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

/**
 * Helps generate php representation of a template.
 *
 * @package phptal.php
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_Php_CodeWriter
{
    public function __construct(PHPTAL_Php_State $state)
    {
        $this->_state = $state;
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
        foreach($array as &$a) $a = str_replace(';;',';',$a);
        return $array;
    }

    public function evaluateExpression($src)
    {
        return $this->_state->evalTalesExpression($src);
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
        if ($bool){
            $this->pushCode('$ctx->noThrow(true)');
        }
        else {
            $this->pushCode('$ctx->noThrow(false)');
        }
    }
    
    public function flushCode()
    {
        if (count($this->_codeBuffer) == 0) 
            return;

        // special treatment for one code line
        if (count($this->_codeBuffer) == 1){
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
        if ($this->_doctype){
            $code = '$ctx->setDocType('.$this->str($this->_doctype).')';
            $this->pushCode($code);
        }
    }

    public function doXmlDeclaration()
    {
        if ($this->_xmldeclaration){
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
        $this->pushCode("foreach ($source as \$__key__ => $out ):");
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
        }
        else if ($segment == 'try')
            $this->pushCode('}');
        else if ($segment == 'catch')
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
        $code = 'catch(%s) {';
        $this->pushCode(sprintf($code, $catch));
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
        $html = '<?php echo %s ?>';
        $html = sprintf($html, $this->escapeCode($code));
        $this->pushHtml($html);
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

	public function pushRawHtml($html)
	{
		$this->flushCode();
		$this->_htmlBuffer[] =  $html;
	}

    public function pushString($str)
    {
        $this->flushCode();
       
        // replace ${var} inside strings
        while (preg_match('/^(.*?)((?<!\$)\$\{[^\}]*?\})(.*?)$/s', $str, $m)){
            list(,$before,$expression,$after) = $m;

            $before = $this->escapeLTandGT($before);
            $this->_htmlBuffer[] =  $before;

            $expression = $this->_state->interpolateTalesVarsInHtml($expression);
            $this->_htmlBuffer[] =  $expression;

            $str = $after;
        }

		$str = str_replace('$${', '${', $str);
        
        if (strlen($str) > 0){
            $str = $this->escapeLTandGT($str);
            $this->_htmlBuffer[] =  $str;
        }
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

    public function escapeLTandGT($str){
        $str = str_replace('<', '&lt;', $str);
        $str = str_replace('>', '&gt;', $str);
        return $str;
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
            $attr_regex = '/^[^&\/=\'"><\s`\pM\pC\pZ\p{Pc}\p{Sk}]+$/u';
        }
        else
        {
            $attr_regex = '/^[^&\/=\'"><\s`\0177-\377]+$/';
        }
        
        if ($this->getOutputMode() == PHPTAL::HTML5 && preg_match($attr_regex,$value)) 
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


