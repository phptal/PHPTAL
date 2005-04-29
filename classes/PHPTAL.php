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

define('PHPTAL_VERSION', '1_0_9');

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
require_once 'PHPTAL/Exception.php';


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
    
    /**
     * PHPTAL Constructor.
     *
     * @param string $path Template file path.
     */
    public function __construct($path=false)
    {//{{{
        $this->_realPath = $path;
        $this->_repositories = array();
        if (defined('PHPTAL_TEMPLATE_REPOSITORY')){
            $this->_repositories[] = PHPTAL_TEMPLATE_REPOSITORY;
        }
        $this->_context = new PHPTAL_Context();
    }//}}}

    /**
     * Clone template state and context.
     */
    public function __clone()
    {//{{{
        $this->_context = clone $this->_context;
    }//}}}

    /**
     * Set template file path.
     */
    public function setTemplate($path)
    {//{{{
        $this->_realPath = $path;
    }//}}}

    /**
     * Set template source.
     *
     * Should be used only with temporary template sources, prefer plain
     * files.
     *
     * @param $src string The phptal template source.
     * @param path string Fake and 'unique' template path.
     */
    public function setSource($src, $path=false)
    {//{{{
        $this->_source = $src;
        if ($path){
            $this->_realPath = $path;
        }
        else {
            $this->_realPath = '<string> '.md5($src);
        }
    }//}}}
    
    /**
     * Specify where to look for templates.
     *
     * @param $rep String or Array of repositories
     */
    public function setTemplateRepository($rep)
    {//{{{
        if (is_array($rep)){
            $this->_repositories = $rep;
        }
        else {
            $this->_repositories[] = $rep;
        }
    }//}}}

    /**
     * Ignore XML/XHTML comments on parsing.
     */
    public function stripComments($bool)
    {//{{{
        $this->_stripComments = $bool;
    }//}}}
    
    /**
     * Set output mode (PHPTAL::XML or PHPTAL::XHTML).
     */
    public function setOutputMode($mode=PHPTAL_XHTML)
    {//{{{
        if ($mode != PHPTAL::XHTML && $mode != PHPTAL::XML){
            throw new PHPTAL_Exception('Unsupported output mode '.$mode);
        }
        $this->_outputMode = $mode;
    }//}}}

    /**
     * Set ouput encoding.
     */
    public function setEncoding($enc)
    {//{{{
        $this->_encoding = $enc; 
    }//}}}

    /**
     * Set I18N translator.
     */
    public function setTranslator($t)
    {//{{{
        $this->_translator = $t;
    }//}}}

    /**
     * Set template pre filter.
     */
    public function setPreFilter(PHPTAL_Filter $filter)
    {//{{{
        $this->_prefilter = $filter;
    }//}}}

    /**
     * Set template post filter.
     */
    public function setPostFilter(PHPTAL_Filter $filter)
    {//{{{
        $this->_postfilter = $filter;
    }//}}}

    /**
     * Register a trigger for specified phptal:id.
     */
    public function addTrigger($id, PHPTAL_Trigger $trigger)
    {//{{{
        $this->_triggers[$id] = $trigger;
    }//}}}

    /**
     * Returns trigger for specified phptal:id.
     */
    public function getTrigger($id)
    {//{{{
        if (array_key_exists($id, $this->_triggers)){
            return $this->_triggers[$id];
        }
        return null;
    }//}}}

    /**
     * Set a context variable.
     */
    public function __set($varname, $value)
    {//{{{
        $this->_context->__set($varname, $value);
    }//}}}

    /**
     * Set a context variable.
     */
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
       
        // includes generated template PHP code
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
        // extract macro source file from macro name, if not source file
        // found in $path, then the macro is assumed to be local
        if (preg_match('/^(.*?)\/([a-z0-9_]*?)$/i', $path, $m)){
            list(,$file,$macroName) = $m;
            
            // search for file in current template folder first
            // TODO: ensuring that the macro file exists and looking for its
            // location must be done at compile time instead of there, it will
            // greatly improve macro call performances
            $f = dirname($this->_realPath).PHPTAL_PATH_SEP.$file;
            if (file_exists($f)){
                $file = $f;
            }
   
            // ensure that the macro file is prepared and translated into PHP
            // code before executing it.
            // TODO: stores a list of already prepared macro to avoid this 
            // creation on each call
            $tpl = new PHPTAL($file);
            $tpl->_encoding = $this->_encoding;
            $tpl->setTemplateRepository($this->_repositories);
            $tpl->prepare();

            // save current file
            $currentFile = $this->_context->__file;            
            $this->_context->__file = $tpl->__file;
            
            // require PHP generated code and execute macro function
            require_once $tpl->getCodePath();
            $fun = $tpl->getFunctionName() . '_' . $macroName;
            $fun($this, $this->_context);
            
            // restore current file
            $this->_context->__file = $currentFile;
        }
        else {
            // call local macro
            $fun = $this->getFunctionName() . '_' . trim($path);
            $fun( $this, $this->_context );            
        }
    }//}}}

    /**
     * Prepare template without executing it.
     */
    public function prepare()
    {//{{{
        // find the template source file
        $this->findTemplate();
        $this->__file = $this->_realPath;
        // where php generated code should resides
        $this->_codeFile = PHPTAL_PHP_CODE_DESTINATION 
                         . $this->getFunctionName() 
                         . '.php';
        // parse template if php generated code does not exists or template
        // source file modified since last generation of PHPTAL_FORCE_REPARSE
        // is defined.
        if (defined('PHPTAL_FORCE_REPARSE') 
            || !file_exists($this->_codeFile) 
            || (!$this->_source && filemtime($this->_codeFile) < filemtime($this->_realPath))) {
            $this->parse();
        }
        $this->_prepared = true;
    }//}}}

    /**
     * Returns the path of the intermediate PHP code file.
     *
     * The returned file may be used to cleanup (unlink) temporary files
     * generated by temporary templates or more simply for debug.
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
            $this->_functionName = 
                'tpl_' .
                PHPTAL_VERSION. 
                md5($this->_realPath);
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
    public function addError($error)
    {//{{{
        array_push($this->_errors, $error); 
    }//}}}

    /**
     * Returns current context object.
     */
    public function getContext()
    {//{{{
        return $this->_context;
    }//}}}
    
    private function parse()
    {//{{{
        require_once 'PHPTAL/Parser.php';
        require_once 'PHPTAL/CodeGenerator.php';
        
        // instantiate the PHPTAL source parser 
        $parser = new PHPTAL_Parser();
        $parser->stripComments($this->_stripComments);
        $parser->setPreFilter($this->_prefilter);

        // source may be provided string or template file
        if (isset($this->_source)){
            $tree = $parser->parseString($this->_source);
        }
        else {
            $tree = $parser->parseFile($this->_realPath);
        }
        
        // instantiate a new PHP code generator for this template
        $generator = new PHPTAL_CodeGenerator($this->_encoding);
        $generator->setOutputMode($this->_outputMode);
        $tree->setGenerator($generator);

        // generate the PHP code
        $header = sprintf('Generated by PHPTAL from %s', $this->_realPath);
        $generator->doFunction($this->_functionName, '$tpl, $ctx');
        $generator->doComment($header);
        $generator->setFunctionPrefix($this->_functionName . "_");
        $generator->pushCode('ob_start()');
        $tree->generate();
        $generator->pushCode('$_result_ = ob_get_contents()');
        $generator->pushCode('ob_end_clean()');
        $generator->pushCode('return $_result_');
        $generator->doEnd();
        
        // and store it into temporary file
        $this->storeGeneratedCode($generator->getResult());
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

    /** Search template source location. */
    private function findTemplate()
    {//{{{
        if ($this->_realPath == false){
            throw new Exception('No template file specified');
        }

        // source string provided manually
        if (isset($this->_source)){ 
            return;
        }
        
        // search into template repositories
        foreach ($this->_repositories as $repository){
            $f = $repository . PHPTAL_PATH_SEP . $this->_realPath;
            if (file_exists($f)){
                $this->_realPath = $f;
                return;
            }
        }
        
        // fail back to current path (or absolute path)
        $path = $this->_realPath;
        if (file_exists($path)) return;
        
        // not found
        $err = 'Unable to locate template file %s';
        $err = sprintf($err, $this->_realPath);
        throw new Exception($err);
    }//}}}

    private $_prefilter = null;
    private $_postfilter = null;

    // list of template source repositories
    private $_repositories = array();
    // template path
    private $_realPath;
    // template source (only set when not working with file)
    private $_source;
    // destination of PHP intermediate file
    private $_codeFile;
    // php function generated for the template
    private $_functionName;
    // set to true when template is ready for execution
    private $_prepared = false;
    
    // associative array of phptal:id => PHPTAL_Trigger
    private $_triggers = array();
    // i18n translator
    private $_translator = null;

    // current execution context
    private $_context;
    // current template file (changes within macros)
    public  $__file = false;
    // list of on-error caught exceptions
    private $_errors = array();

    private $_encoding = PHPTAL_DEFAULT_ENCODING; 
    private $_outputMode = PHPTAL_XHTML;
    private $_stripComments = false;
}

?>
