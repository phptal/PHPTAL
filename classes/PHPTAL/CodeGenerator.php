<?php

/**
 * @package PHPTAL
 */
class PHPTAL_CodeGenerator
{
    public function getResult()
    {
        $this->flush();
        $this->_result = trim($this->_result);
        return $this->_result;
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
    
    public function flushCode()
    {
        if (count( $this->_codeBuffer ) == 0) return;
        if (count( $this->_codeBuffer ) == 1) {
            $codeLine = $this->_codeBuffer[0];
            // avoid adding ; after }
            if (!preg_match('/\}|\{\s+$/', $codeLine))
                $this->_result .= "<?php $codeLine; ?>";
            else 
                $this->_result .= "<?php $codeLine ?>";
        }
        else {
            $this->_result .= "<?php \n";
            foreach ($this->_codeBuffer as $codeLine) {
                // avoid adding ; after }
                if (!preg_match('/\}|\{\s+$/', $codeLine))
                    $this->_result .= $codeLine . " ;\n";
                else 
                    $this->_result .= $codeLine;
            }
            $this->_result .= "?>";
        }
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

    public function doEcho( $code )
    {
        $this->flush();
        $this->pushHtml( "<?= htmlentities( $code, ENT_COMPAT, 'UTF-8' ) ?>" );
    }

    public function pushHtml( $html )
    {
        $this->flushCode();
        array_push( $this->_htmlBuffer, $html );
    }

    public function pushString( $str )
    {
        $this->flushCode();
        array_push( $this->_htmlBuffer, htmlentities($str, ENT_COMPAT, 'UTF-8') );
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
    
    private $_result = "";
    private $_indent = 0;
    private $_codeBuffer = array();
    private $_htmlBuffer = array();
    private $_segments = array();
    private $_talesMode = 'tales';
    private $_contexts = array();
    private $_functionPrefix = "";
    private $_doctype = "";
}

?>
