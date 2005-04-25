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
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_CodeGenerator
{
    function __construct($encoding='UTF-8')
    {//{{{
        $this->_encoding = $encoding;
    }//}}}
    
    public function getResult()
    {//{{{
        $this->flush();
        $this->_result = trim($this->_result);
        return $this->_result;
    }//}}}

    public function setOutputMode($mode)
    {//{{{
        $this->_outputMode = $mode;
    }//}}}
    
    public function getOutputMode()
    {//{{{
        return $this->_outputMode;
    }//}}}

    public function getEncoding()
    {//{{{
        return $this->_encoding;
    }//}}}

    public function setDocType($dt)
    {//{{{
        $this->_doctype = $dt;
    }//}}}

    public function getDocType()
    {//{{{
        return $this->_doctype;
    }//}}}

    public function setXmlDeclaration($dt)
    {//{{{
        $this->_xmldeclaration = $dt;
    }//}}}

    public function getXmlDeclaration()
    {//{{{
        return $this->_xmldeclaration;
    }//}}}
    
    public function setFunctionPrefix($prefix)
    {//{{{
        $this->_functionPrefix = $prefix;
    }//}}}

    public function getFunctionPrefix()
    {//{{{
        return $this->_functionPrefix;
    }//}}}

    /**
     * Returns old tales mode.
     */
    public function setTalesMode($mode)
    {//{{{
        $old = $this->_talesMode;
        $this->_talesMode = $mode;
        return $old;
    }//}}}

    public function splitExpression($src)
    {//{{{
        return preg_split('/(?<!;);(?!;)/sm', $src);
    }//}}}

    public function evaluateExpression($src)
    {//{{{
        if ($this->_talesMode == 'php'){
            return phptal_tales_php($src);
        }
        else {
            return phptal_tales($src);
        }
    }//}}}
    
    public function indent() 
    {//{{{
        $this->_indent ++; 
    }//}}}
    
    public function unindent() 
    {//{{{
        $this->_indent --; 
    }//}}}
    
    public function flush() 
    {//{{{
        $this->flushCode();
        $this->flushHtml();
    }//}}}

    public function noThrow($bool)
    {//{{{
        if ($bool){
            $this->pushCode('$ctx->noThrow(true)');
        }
        else {
            $this->pushCode('$ctx->noThrow(false)');
        }
    }//}}}
    
    public function flushCode()
    {//{{{
        $nlines = count($this->_codeBuffer);
        if ($nlines > 0){
            $newline = $nlines > 1 ? "\n" : '';
            $this->_result .= '<?php '.$newline;
            foreach ($this->_codeBuffer as $codeLine){
                $this->_result .=  $codeLine;
                if (!preg_match('/\}|\{\s+$/', $codeLine)){
                    $this->_result .= '; '.$newline;
                }
            }
            $this->_result .= '?>';
            $this->_codeBuffer = array();
        }
    }//}}}
    
    public function flushHtml()
    {//{{{
        if (count($this->_htmlBuffer) > 0){
            $this->_result .= implode('', $this->_htmlBuffer);
            $this->_htmlBuffer = array();
        }
    }//}}}
    
    public function doFunction($name, $params)
    {//{{{
        $name = $this->_functionPrefix . $name;
        $this->pushGeneratorContext();
        $this->pushCode("function $name( $params ) {\n");
        $this->indent();
        array_push($this->_segments, 'function');
    }//}}}
    
    public function doComment($comment)
    {//{{{
        $comment = str_replace('*/', '* /', $comment);
        $this->pushCode("/* $comment */");
    }//}}}

    public function doEval($code)
    {//{{{
        $this->pushCode($code);
    }//}}}
                       
    public function doForeach($out, $source)
    {//{{{
        array_push($this->_segments, 'foreach');
        $this->pushCode("foreach ($source as \$__key__ => $out ):");
        $this->indent();
    }//}}}

    public function doEnd()
    {//{{{
        $segment = array_pop($this->_segments);
        $this->unindent();
        if ($segment == 'function') {
            $this->pushCode("\n}\n\n");
            $functionCode = $this->getResult();
            $this->popGeneratorContext();
            $this->_result = $functionCode . $this->_result;
        }
        else if ($segment == 'try')
            $this->pushCode('}');
        else if ($segment == 'catch')
            $this->pushCode('}');
        else 
            $this->pushCode("end$segment");
    }//}}}

    public function doTry()
    {//{{{
        array_push($this->_segments, 'try');
        $this->pushCode('try {');
        $this->indent();
    }//}}}

    public function doCatch($catch)
    {//{{{
        $this->doEnd();
        array_push($this->_segments, 'catch');
        $code = 'catch(%s) {';
        $this->pushCode(sprintf($code, $catch));
        $this->indent();
    }//}}}

    public function doIf($condition)
    {//{{{
        array_push($this->_segments, 'if');
        $this->pushCode("if ($condition): ");
        $this->indent();
    }//}}}

    public function doElseIf($condition)
    {//{{{
        $this->unindent();
        $this->pushCode("elseif ($condition): ");
        $this->indent();
    }//}}}

    public function doElse()
    {//{{{
        $this->unindent();
        $this->pushCode("else: ");
        $this->indent();
    }//}}}

    public function doEcho($code, $replaceInString=true)
    {//{{{
        $this->flush();
        $html = '<?php echo %s ?>';
        $html = sprintf($html, $this->escapeCode($code));
        $this->pushHtml($html, $replaceInString);
    }//}}}

    public function pushHtml($html, $replaceInString=true)
    {//{{{
        if ($replaceInString)
            $html = $this->_replaceInStringExpression($html);
        $this->flushCode();
        array_push($this->_htmlBuffer, $html);
    }//}}}

    public function pushString($str)
    {//{{{
        $this->flushCode();
       
        // replace ${var} inside strings
        while (preg_match('/^(.*?)(\$\{[^\}]*?\})(.*?)$/s', $str, $m)){
            list(,$before,$expression,$after) = $m;
            
            $before = $this->escape($before);
            $before = str_replace('&amp;', '&', $before);
            array_push($this->_htmlBuffer, $before);

            $expression = $this->_replaceInStringExpression($expression);
            array_push($this->_htmlBuffer, $expression);

            $str = $after;
        }
        
        if (strlen($str) > 0){
            $str = $this->escape($str); 
            $str = str_replace('&amp;', '&', $str);
            array_push($this->_htmlBuffer, $str);
        }
    }//}}}

    public function pushCode($codeLine) 
    {//{{{
        $this->flushHtml();
        $codeLine = $this->indentSpaces() . $codeLine;
        array_push($this->_codeBuffer, $codeLine);
    }//}}}

    public function escapeCode($code)
    {//{{{
        $result = '%s(%s, ENT_QUOTES, \'%s\')';
        return sprintf($result, $this->_htmlEscapingFunction, $code, $this->_encoding);
    }//}}}

    public function escape($html)
    {//{{{
        $func = $this->_htmlEscapingFunction;
        return $func($html, ENT_QUOTES, $this->_encoding);
    }//}}}

    public function evaluateTalesString($src)
    {//{{{
        if ($this->_talesMode == 'tales'){
            return phptal_tales_string($src);
        }
        
        // replace ${var} found in expression
        while (preg_match('/\$\{([^\}]+)\}/ism', $src, $m)){
            list($ori, $exp) = $m;
            $php  = phptal_tales_php($exp);
            $repl = '\'.%s.\''; 
            $repl = sprintf($repl, $php);
            $src = str_replace($ori, $repl, $src);
        }
        return '\''.$src.'\'';
    }//}}}

    public function setDebug($bool)
    {//{{{
        $old = $this->_debug;
        $this->_debug = $bool;
        return $this->_debug;
    }//}}}
    
    public function isDebugOn()
    {//{{{
        return $this->_debug;
    }//}}}

    public function setHtmlEscaping($function, $ent=END_QUOTES)
    {//{{{
        $this->_htmlEscapingFunction = $function;
    }//}}}
 
    // ~~~~~ Private members ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    
    private function indentSpaces() 
    {//{{{
        return str_pad('', $this->_indent * 4); 
    }//}}}

    private function pushGeneratorContext()
    {//{{{
        array_push($this->_contexts, clone $this);
        $this->_result = "";
        $this->_indent = 0;
        $this->_codeBuffer = array();
        $this->_htmlBuffer = array();
        $this->_segments = array();
    }//}}}
    
    private function popGeneratorContext()
    {//{{{
        $oldContext = array_pop($this->_contexts);
        $this->_result = $oldContext->_result;
        $this->_indent = $oldContext->_indent;
        $this->_codeBuffer = $oldContext->_codeBuffer;
        $this->_htmlBuffer = $oldContext->_htmlBuffer;
        $this->_segments = $oldContext->_segments;
        $this->_talesMode = $oldContext->_talesMode;
    }//}}}

    private function _replaceInStringExpression($src)
    {//{{{
        if ($this->_talesMode == 'tales'){
            return preg_replace(
                '/\$\{([a-z0-9\/_]+)\}/ism', 
                '<?php echo '
                .$this->_htmlEscapingFunction.'( '
                .'phptal_path($ctx, \'$1\'), ENT_QUOTES, \''.$this->_encoding.'\' '
                .') ?>',
                $src);
        }

        while (preg_match('/\${(structure )?([^\}]+)\}/ism', $src, $m)){
            list($ori, $struct, $exp) = $m;
            $php  = phptal_tales_php($exp);
            $repl = '<?php echo %s; ?>';
            // when structure keyword is specified the output is not html 
            // escaped
            if ($struct){
                $repl = sprintf($repl, $php);
            }
            else {
                $repl = sprintf($repl, $this->escapeCode($php));
            }
            $src  = str_replace($ori, $repl, $src);
        }
       
        return $src;
    }//}}}

    private $_debug  = false;
    private $_result = "";
    private $_indent = 0;
    private $_codeBuffer = array();
    private $_htmlBuffer = array();
    private $_segments = array();
    private $_talesMode = 'tales';
    private $_contexts = array();
    private $_functionPrefix = "";
    private $_doctype = "";
    private $_xmldeclaration = "";
    private $_encoding;
    private $_outputMode;
    private $_htmlEscapingFunction = 'htmlspecialchars';
}

?>
