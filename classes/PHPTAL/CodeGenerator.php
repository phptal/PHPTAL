<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004 Laurent Bedubourg
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
    function __construct( $encoding='UTF-8' )
    {
        $this->_encoding = $encoding;
    }
    
    public function getResult()
    {
        $this->flush();
        $this->_result = trim($this->_result);
        return $this->_result;
    }

    public function setOutputMode($mode)
    {
        $this->_outputMode = $mode;
    }
    
    public function getOutputMode()
    {
        return $this->_outputMode;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    public function setDocType( $dt )
    {
        $this->_doctype = $dt;
    }

    public function getDocType()
    {
        return $this->_doctype;
    }
    
    public function setFunctionPrefix( $prefix )
    {
        $this->_functionPrefix = $prefix;
    }

    public function getFunctionPrefix()
    {
        return $this->_functionPrefix;
    }

    /**
     * @return string Old mode
     */
    public function setTalesMode( $mode )
    {
        $old = $this->_talesMode;
        $this->_talesMode = $mode;
        return $old;
    }

    public function splitExpression( $src )
    {
        return preg_split('/(?<!;);(?!;)/sm', $src);
    }

    public function evaluateExpression( $src )
    {
        if ($this->_talesMode == 'php'){
            return phptal_tales_php($src);
        }
        else {
            return phptal_tales($src);
        }
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

    public function noThrow( $bool )
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
        if (count( $this->_codeBuffer ) == 0) 
            return;

        // special treatment for one code line
        if (count( $this->_codeBuffer ) == 1) {
            $codeLine = $this->_codeBuffer[0];
            // avoid adding ; after } and {
            if (!preg_match('/\}|\{\s+$/', $codeLine))
                $this->_result .= "<?php $codeLine; ?>";
            else 
                $this->_result .= "<?php $codeLine ?>";

            $this->_codeBuffer = array();
            return;
        }
    
        $this->_result .= "<?php \n";
        foreach ($this->_codeBuffer as $codeLine) {
            // avoid adding ; after } and {
            if (!preg_match('/\}|\{\s+$/', $codeLine))
                $this->_result .= $codeLine . " ;\n";
            else 
                $this->_result .= $codeLine;
        }
        $this->_result .= "?>";
        $this->_codeBuffer = array();
    }
    
    public function flushHtml()
    {
        if (count( $this->_htmlBuffer ) == 0) return;
        
        $this->_result .= join( "", $this->_htmlBuffer );
        $this->_htmlBuffer = array();
    }
    
    public function doFunction( $name, $params )
    {
        $name = $this->_functionPrefix . $name;
        $this->pushGeneratorContext();
        $this->pushCode( "function $name( $params ) {\n" );
        $this->indent();
        array_push( $this->_segments, 'function' );
    }
    
    public function doComment( $comment )
    {
        $comment = str_replace('*/', '* /', $comment);
        $this->pushCode( "/* $comment */" );
    }

    public function doEval( $code )
    {
        $this->pushCode( $code );
    }
                       
    public function doForeach( $out, $source )
    {
        array_push( $this->_segments, 'foreach' );
        $this->pushCode( "foreach ($source as \$__key__ => $out ):" );
        $this->indent();
    }

    public function doEnd()
    {
        $segment = array_pop( $this->_segments );
        $this->unindent();
        if ($segment == 'function') {
            $this->pushCode( "\n}\n\n" );
            $functionCode = $this->getResult();
            $this->popGeneratorContext();
            $this->_result = $functionCode . $this->_result;
        }
        else if ($segment == 'try')
            $this->pushCode( '}' );
        else if ($segment == 'catch')
            $this->pushCode( '}' );
        else 
            $this->pushCode( "end$segment" );
    }

    public function doTry()
    {
        array_push( $this->_segments, 'try');
        $this->pushCode('try {');
        $this->indent();
    }

    public function doCatch( $catch )
    {
        $this->doEnd();
        array_push( $this->_segments, 'catch');
        $code = 'catch(%s) {';
        $this->pushCode(sprintf($code, $catch));
        $this->indent();
    }

    public function doIf( $condition )
    {
        array_push( $this->_segments, 'if' );
        $this->pushCode( "if ($condition): " );
        $this->indent();
    }

    public function doElseIf( $condition )
    {
        $this->unindent();
        $this->pushCode( "elseif ($condition): ");
        $this->indent();
    }

    public function doElse()
    {
        $this->unindent();
        $this->pushCode( "else: ");
        $this->indent();
    }

    public function doEcho( $code, $replaceInString=true )
    {
        $this->flush();
        $this->pushHtml( "<?php echo htmlentities( $code, ENT_COMPAT, '$this->_encoding' ) ?>", $replaceInString );
    }

    public function pushHtml( $html, $replaceInString=true )
    {
        if ($replaceInString)
            $html = $this->_replaceInStringExpression($html);
        $this->flushCode();
        array_push( $this->_htmlBuffer, $html );
    }

    public function pushString( $str )
    {
        $str = htmlentities($str, ENT_COMPAT, $this->_encoding);

        // string may contains &nbsp; and other stuff which should not be
        // encoded here, thus we restore them keeping characters encoding
        // conversion given by $this->_encoding
        $str = str_replace('&amp;', '&', $str);
        
        $str = $this->_replaceInStringExpression($str);
        
        $this->flushCode();
        array_push( $this->_htmlBuffer, $str );
    }

    public function pushCode( $codeLine ) 
    {
        $this->flushHtml();
        $codeLine = $this->indentSpaces() . $codeLine;
        array_push( $this->_codeBuffer, $codeLine );
    }


    private function indentSpaces() 
    { 
        return str_pad('', $this->_indent * 4); 
    }

    private function pushGeneratorContext()
    {
        array_push($this->_contexts, clone $this);
        $this->_result = "";
        $this->_indent = 0;
        $this->_codeBuffer = array();
        $this->_htmlBuffer = array();
        $this->_segments = array();
    }
    
    private function popGeneratorContext()
    {
        $oldContext = array_pop($this->_contexts);
        $this->_result = $oldContext->_result;
        $this->_indent = $oldContext->_indent;
        $this->_codeBuffer = $oldContext->_codeBuffer;
        $this->_htmlBuffer = $oldContext->_htmlBuffer;
        $this->_segments = $oldContext->_segments;
        $this->_talesMode = $oldContext->_talesMode;
    }


    private function _replaceInStringExpression($src)
    {
        if ($this->_talesMode == 'tales'){
            return preg_replace(
                '/\$\{([a-z0-9\/_]+)\}/ism', 
                '<?php echo htmlentities( phptal_path($ctx, \'$1\'), ENT_COMPAT, \''.$this->_encoding.'\' ) ?>',
                $src);
        }

        while (preg_match('/\${structure ([^\}]+)\}/ism', $src, $m)){
            list($ori, $exp) = $m;
            $php  = phptal_tales_php($exp);
            $repl = '<?php echo %s; ?>';
            $repl = sprintf($repl, $php);
            $src  = str_replace($ori, $repl, $src);
        }
        
        while (preg_match('/\$\{([^\}]+)\}/ism', $src, $m)){
            list($ori, $exp) = $m;
            $php  = phptal_tales_php($exp);
            $repl = '<?php echo htmlentities( %s , ENT_COMPAT, \'%s\') ?>';
            $repl = sprintf($repl, $php, $this->_encoding);
            $src  = str_replace($ori, $repl, $src);
        }
        return $src;
    }

    public function evaluateTalesString($src)
    {
        if ($this->_talesMode == 'tales'){
            return phptal_tales_string($src);
        }
        
        while (preg_match('/\$\{([^\}]+)\}/ism', $src, $m)){
            list($ori, $exp) = $m;
            $php  = phptal_tales_php($exp);
            $repl = '\'.%s.\''; 
            $repl = sprintf($repl, $php, $this->_encoding);
            $src = str_replace($ori, $repl, $src);
        }
        return '\''.$src.'\'';
    }

    public function setDebug($bool)
    {
        $old = $this->_debug;
        $this->_debug = $bool;
        return $this->_debug;
    }
    
    public function isDebugOn()
    {
        return $this->_debug;
    }
 
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
    private $_encoding;
    private $_outputMode;
}

?>
