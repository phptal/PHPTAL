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

define('PHPTAL_VERSION', '1_0_5');

//{{{OS RELATED DEFINES
if (substr(PHP_OS,0,3) == 'WIN'){
    define('PHPTAL_OS_WIN', true);
    define('PHPTAL_PATH_SEP', '\\');
}
else {
    define('PHPTAL_OS_WIN', false);
    define('PHPTAL_PATH_SEP', '/');
}
//}}}
//{{{PHPTAL_PHP_CODE_DESTINATION
if (!defined('PHPTAL_PHP_CODE_DESTINATION')){
    if (PHPTAL_OS_WIN){
        if (file_exists('c:\\WINNT\\Temp\\')){
            define('PHPTAL_PHP_CODE_DESTINATION', 'c:\\WINNT\\Temp\\');
        }
        else {
            define('PHPTAL_PHP_CODE_DESTINATION', 'c:\\WINDOWS\\Temp\\');
        }
    }
    else {
        define('PHPTAL_PHP_CODE_DESTINATION', '/tmp/');
    }
}
//}}}
//{{{PHPTAL_DEFAULT_ENCODING
if (!defined('PHPTAL_DEFAULT_ENCODING')){
    define('PHPTAL_DEFAULT_ENCODING', 'UTF-8');
}
//}}}

define('PHPTAL_XHTML', 1);
define('PHPTAL_XML',   2);

require_once 'PHPTAL/RepeatController.php';
require_once 'PHPTAL/Context.php';


class PHPTAL_Exception extends Exception
{
    public $srcFile;
    public $srcLine;

    public function __construct($msg, $srcFile=false, $srcLine=false)
    {//{{{
        parent::__construct($msg);
        $this->srcFile = $srcFile;
        $this->srcLine = $srcLine;
    }//}}}

    public function __toString()
    {//{{{
        if (!$this->srcFile){
            return parent::__toString();
        }
        $res = sprintf("From %s around line %d\n", $this->srcFile, $this->srcLine);
        $res .= parent::__toString();
        return $res;
    }//}}}
}


/**
 * PHPTAL template entry point.
 * 
 * <code>
 * <?php
 * require_once 'PHPTAL.php';
 * try {
 *      $tpl = new PHPTAL('mytemplate.html');
 *      $tpl->title = 'Welcome here';
 *      $tpl->result = range(1, 100);
 *      ...
 *      echo $tpl->execute();
 * }
 * catch (Exception $e) {
 *      echo $e;
 * }
 * ?>
 * </code>
 * 
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL 
{
    const XHTML = 1;
    const XML   = 2;
    
    public function __construct($path=false)
    {//{{{
        $this->_realPath = $path;
        $this->_repositories = array();
        if (defined('PHPTAL_TEMPLATE_REPOSITORY')){
            $this->_repositories[] = PHPTAL_TEMPLATE_REPOSITORY;
        }
        $this->_context = new PHPTAL_Context();
    }//}}}

    public function setTemplate($path)
    {//{{{
        $this->_realPath = $path;
    }//}}}

    public function __clone()
    {//{{{
        $this->_context = clone $this->_context;
    }//}}}

    /**
     * Specify where to look for templates.
     *
     * @param $rep String or Array of repositories
     */
    public function setTemplateRepository( $rep )
    {//{{{
        if (is_array($rep)){
            $this->_repositories = $rep;
        }
        else {
            $this->_repositories[] = $rep;
        }
    }//}}}
    
    public function setOutputMode( $mode=PHPTAL_XHTML )
    {//{{{
        $this->_outputMode = $mode;
    }//}}}

    public function setEncoding( $enc )
    {//{{{
        $this->_encoding = $enc; 
    }//}}}

    public function setTranslator( $t )
    {//{{{
        $this->_translator = $t;
    }//}}}

    public function setPreFilter(PHPTAL_Filter $filter)
    {//{{{
        $this->_prefilter = $filter;
    }//}}}

    public function setPostFilter(PHPTAL_Filter $filter)
    {//{{{
        $this->_postfilter = $filter;
    }//}}}

    public function addTrigger($id, PHPTAL_Trigger $trigger)
    {//{{{
        $this->_triggers[$id] = $trigger;
    }//}}}

    public function getTrigger($id)
    {//{{{
        if (array_key_exists($id, $this->_triggers)){
            return $this->_triggers[$id];
        }
        return null;
    }//}}}

    public function __set($varname, $value)
    {//{{{
        $this->_context->__set($varname, $value);
    }//}}}

    public function set($varname, $value)
    {//{{{
        $this->_context->__set($varname, $value);
    }//}}}
    
    /**
     * Execute the template code.
     *
     * @return string
     */
    public function execute() 
    {//{{{
        if (!$this->_prepared) {
            $this->prepare();
        }
        
        $this->_context->__file = $this->__file;
        require_once $this->_codeFile;
        $templateFunction = $this->_functionName;
        try {
            $res = $templateFunction($this, $this->_context);
        }
        catch (Exception $e){
            ob_end_clean();
            throw $e;
        }

        // unshift doctype
        $docType = $this->_context->__docType;
        if ($docType){
            $res = $docType . "\n" . $res;
        }
        // unshift xml declaration
        $xmlDec = $this->_context->__xmlDeclaration;
        if ($xmlDec){
            $res = $xmlDec . "\n" . $res;
        }
        
        if ($this->_postfilter != null){
            return $this->_postfilter->filter($res);
        }
        return $res;
    }//}}}

    /**
     * Execute a template macro.
     */
    public function executeMacro($path)
    {//{{{
        if (preg_match('/^(.*?)\/([a-z0-9_]*?)$/i', $path, $m)){
            list(,$file,$macroName) = $m;
            
            $f = dirname($this->_realPath).PHPTAL_PATH_SEP.$file;
            if (file_exists($f)){
                $file = $f;
            }
    
            $tpl = new PHPTAL( $file );
            $tpl->_encoding = $this->_encoding;
            $tpl->setTemplateRepository($this->_repositories);
            $tpl->prepare();

            $currentFile = $this->_context->__file;
            $this->_context->__file = $tpl->__file;
            require_once $tpl->getCodePath();
            $fun = $tpl->getFunctionName() . '_' . $macroName;
            $fun( $this, $this->_context );
            $this->_context->__file = $currentFile;
        }
        else {
            $fun = $this->getFunctionName() . '_' . trim($path);
            $fun( $this, $this->_context );            
        }
    }//}}}

    /**
     * Prepare template without executing it.
     */
    public function prepare()
    {//{{{
        $this->findTemplate();
        $this->__file = $this->_realPath;
        $this->_codeFile = PHPTAL_PHP_CODE_DESTINATION 
                         . $this->getFunctionName() 
                         . '.php';
        if (defined('PHPTAL_FORCE_REPARSE') 
            || !file_exists($this->_codeFile) 
            || filemtime($this->_codeFile) < filemtime($this->_realPath)) {
            $this->parse();
        }
        $this->_prepared = true;
    }//}}}

    /**
     * Returns the path of the intermediate PHP code file.
     */
    public function getCodePath()
    {//{{{
        return $this->_codeFile;
    }//}}}

    /**
     * Returns the generated template function name.
     */
    public function getFunctionName()
    {//{{{
        if (!$this->_functionName) {
            $this->_functionName = "tpl_" .PHPTAL_VERSION. md5($this->_realPath);
        }
        return $this->_functionName;
    }//}}}

    /**
     * Returns template translator.
     */
    public function getTranslator()
    {//{{{
        return $this->_translator;
    }//}}}
    
    /**
     * Returns array of exceptions catched by tal:on-error attribute.
     */
    public function getErrors()
    {//{{{
        return $this->_errors;
    }//}}}
    
    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function addError( $error )
    {//{{{
        array_push($this->_errors, $error); 
    }//}}}

    public function getContext()
    {//{{{
        return $this->_context;
    }//}}}
    
    private function parse()
    {//{{{
        require_once 'PHPTAL/Parser.php';
        require_once 'PHPTAL/CodeGenerator.php';
        
        $generator = new PHPTAL_CodeGenerator($this->_encoding);
        $generator->setOutputMode($this->_outputMode);
        $parser = new PHPTAL_Parser($generator);
        $parser->setPreFilter($this->_prefilter);
        $tree = $parser->parseFile($this->_realPath);

        $header = sprintf('Generated by PHPTAL from %s', $this->_realPath);
        $generator->doFunction($this->_functionName, '$tpl, $ctx');
        $generator->doComment( $header );
        $generator->setFunctionPrefix($this->_functionName . "_");
        $generator->pushCode('ob_start()');
        $tree->generate();
        $generator->pushCode('$_result_ = ob_get_contents()');
        $generator->pushCode('ob_end_clean()');
        $generator->pushCode('return $_result_');
        $generator->doEnd();
        
        $this->storeGeneratedCode( $generator->getResult() );
    }//}}}

    private function storeGeneratedCode($code)
    {//{{{
        $fp = @fopen($this->_codeFile, 'w');
        if (!$fp) {
            $err = 'Unable to open %s for writing';
            $err = sprintf($err, $this->_codeFile);
            throw new Exception($err);
        }
        fwrite($fp, $code);
        fclose($fp);
    }//}}}

    private function findTemplate()
    {//{{{
        if ($this->_realPath == false){
            throw new Exception('No template file specified');
        }
        
        foreach ($this->_repositories as $repository){
            $f = $repository . PHPTAL_PATH_SEP . $this->_realPath;
            if (file_exists($f)){
                $this->_realPath = $f;
                return;
            }
        }
        $path = $this->_realPath;
        if (file_exists($path)) return;
        $err = 'Unable to locate template file %s';
        $err = sprintf($err, $this->_realPath);
        throw new Exception($err);
    }//}}}

    private $_prefilter = null;
    private $_postfilter = null;
    private $_codeFile;
    private $_realPath;
    private $_functionName;
    private $_prepared = false;
    private $_repositories = array();
    private $_errors = array();
    private $_context;
    private $_triggers = array();
    
    private $_translator = null;
    public  $__file = false;

    private $_encoding = PHPTAL_DEFAULT_ENCODING; 
    private $_outputMode = PHPTAL_XHTML;
}


function phptal_path( $base, $path, $nothrow=false )
{//{{{
    $parts   = split('/', $path);
    $current = true;

    while ($current = array_shift($parts)){
        // object handling
        if (is_object($base)){
            // look for method
            if (method_exists($base, $current)){
                $base = $base->$current();
                continue;
            }
            
            // look for variable
            if (isset($base->$current)){
                $base = $base->$current;
                continue;
            }
            
            // look for isset (priority over __get)
            if (method_exists($base, '__isset')){
                if ($base->__isset($current)){
                    $base = $base->$current;
                    continue;
                }
            }
            // ask __get and discard if it returns null
            else if (method_exists($base, '__get')){
                $tmp = $base->$current;
                if (!is_null($tmp)){
                    $base = $tmp;
                    continue;
                }
            }

            // magic method call
            if (method_exists($base, '__call')){
                $base = $base->$current();
                continue;
            }

            // emulate array behaviour
            if (is_numeric($current) && method_exists($base, '__getAt')){
                $base = $base->__getAt($current);
                continue;
            }
            
            if ($nothrow)
                return null;

            $err = 'Unable to find part "%s" in path "%s"';
            $err = sprintf($err, $current, $path);
            throw new Exception($err);
        }

        // array handling
        if (is_array($base)) {
            // key or index
            if (array_key_exists($current, $base)){
                $base = $base[$current];
                continue;
            }

            // virtual methods provided by phptal
            if ($current == 'length' || $current == 'size'){
                $base = count($base);
                continue;
            }

            if ($nothrow)
                return null;

            $err = 'Unable to find array key "%s" in path "%s"';
            $err = sprintf($err, $current, $path);
            throw new Exception($err);
        }

        // string handling
        if (is_string($base)) {
            // virtual methods provided by phptal
            if ($current == 'length' || $current == 'size'){
                $base = strlen($base);
                continue;
            }

            // access char at index
            if (is_int($current)){
                $base = $base[$current];
                continue;
            }
        }

        // if this point is reached, then the part cannot be resolved
        
        if ($nothrow)
            return null;
        
        $err = 'Unable to find part "%s" in path "%s"';
        $err = sprintf($err, $current, $path);
        throw new Exception($err);
    }

    return $base;
}//}}}

function phptal_exists( $ctx, $path )
{//{{{
    // special note: this method may requires to be extended to a full
    // phptal_path() sibling to avoid calling latest path part if it is a
    // method or a function...
    $ctx->noThrow(true);
    $res = phptal_path($ctx, $path, true);
    $ctx->noThrow(false);
    return !is_null($res);
}//}}}

?>
