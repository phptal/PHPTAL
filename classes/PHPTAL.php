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

if (!defined('PHPTAL_PHP_CODE_DESTINATION')){
    define('PHPTAL_PHP_CODE_DESTINATION', '/tmp/');
}
define('PHPTAL_VERSION', '1_0_0');
define('PHPTAL_XHTML',1);
define('PHPTAL_XML', 2);

require_once 'PHPTAL/RepeatController.php';


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
 * @package PHPTAL
 */
class PHPTAL 
{
    public function __construct($path)
    {
        $this->_realPath = $path;
        $this->_repositories = array();
        if (defined('PHPTAL_TEMPLATE_REPOSITORY')){
            $this->_repositories[] = PHPTAL_TEMPLATE_REPOSITORY;
        }
        // $this->_repeat = new PHPTAL_RepeatController();
        $this->_repeat = new stdClass;
    }

    public function __clone()
    {
        $this->_repeat = clone($this->_repeat);
    }

    public function setOutputMode( $mode=PHPTAL_XHTML )
    {
        $this->_outputMode = $mode;
    }

    public function setTemplateRepository( $rep )
    {
        if (is_array($rep)){
            $this->_repositories = $rep;
        }
        else {
            $this->_repositories[] = $rep;
        }
    }
    
    public function execute() 
    {
        if (!$this->_prepared) {
            $this->prepare();
        }
        
        $this->_repeat = new stdClass;
        require_once $this->_codeFile;
        $templateFunction = $this->_functionName;
        try {
            $res = $templateFunction($this);
        }
        catch (Exception $e){
            ob_end_clean();
            throw $e;
        }
        return $this->_docType . $res;
    }

    public function executeMacro($path)
    {
        if (preg_match('/^(.*?)\/([a-z0-9_]*?)$/i', $path, $m)){
            list(,$file,$macroName) = $m;
        }

        if (file_exists(dirname($this->_realPath).'/'.$file)){
            $file = dirname($this->_realPath).'/'.$file;
        }
        
        $tpl = new PHPTAL( $file );
        $tpl->_encoding = $this->_encoding;
        $tpl->setTemplateRepository($this->_repositories);
        $tpl->prepare();

        require_once $tpl->getCodePath();
        $fun = $tpl->getFunctionName() . '_' . $macroName;
        $fun( $this );
    }

    public function prepare()
    {
        $this->findTemplate();
        $this->_codeFile = PHPTAL_PHP_CODE_DESTINATION . $this->getFunctionName() . '.php';
        if (defined('PHPTAL_FORCE_REPARSE') ||
            !file_exists($this->_codeFile) || 
            filemtime($this->_codeFile) < filemtime($this->_realPath)) {
            $this->parse();
        }
        $this->_prepared = true;
    }

    public function getCodePath()
    {
        return $this->_codeFile;
    }

    public function getFunctionName()
    {
        if (!$this->_functionName) {
            $this->_functionName = "tpl_" .PHPTAL_VERSION. md5($this->_realPath);
        }
        return $this->_functionName;
    }

    public function setDocType( $doctype )
    {
        if (!$this->_docType){
            $this->_docType = $doctype;
        }
    }

    public function setEncoding( $enc )
    {
        $this->_encoding = $enc; 
    }

    public function setTranslator( $t )
    {
        $this->_translator = $t;
    }

    public function getTranslator()
    {
        return $this->_translator;
    }

    public function noThrow( $bool )
    {
        $this->_nothrow = $bool;
    }
    
    /**
     * Returns array of exceptions catched by tal:on-error attribute.
     */
    public function getErrors()
    { 
        return $this->_errors;
    }
    
    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function addError( $error )
    {
        array_push($this->_errors, $error); 
    }

    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function hasSlot( $key )
    {
        return array_key_exists($key, $this->_slots); 
    }
    
    /**
     * Public for phptal templates, private for user.
     * @access private
     */    
    public function getSlot( $key )
    {
        return $this->_slots[$key]; 
    }

    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function fillSlot( $key, $content )
    { 
        $this->_slots[$key] = $content; 
    }
    
    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function pushSlots() 
    { 
        array_push($this->_slotsStack, $this->_slots); 
        $this->_slots = array(); 
    }
    
    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function popSlots() 
    { 
        $this->_slots = array_pop($this->_slotsStack); 
    }
   
    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function repeat() 
    { 
        return $this->_repeat; 
    }

    
    public function set($varname, $value)
    {
        if ($varname[0] == '_'){
            throw new Exception("Template variable error '$varname' must not begin with underscore");
        }
        $this->$varname = $value;
    }
    
    public function __get($varname)
    {
        if ($this->_nothrow)
            return null;
        throw new Exception("Unable to find path $varname");
    }

    private function parse()
    {
        require_once 'PHPTAL/Parser.php';
        require_once 'PHPTAL/CodeGenerator.php';
        $generator = new PHPTAL_CodeGenerator($this->_encoding);
        $generator->setOutputMode($this->_outputMode);
        $parser = new PHPTAL_Parser($generator);
        $tree = $parser->parseFile($this->_realPath);

        $header = sprintf('Generated by PHPTAL from %s', $this->_realPath);
        $generator->doFunction($this->_functionName, '$tpl');
        $generator->doComment( $header );
        $generator->setFunctionPrefix($this->_functionName . "_");
        $generator->pushCode('ob_start()');
        $tree->generate();
        $generator->pushCode('$_result_ = ob_get_contents()');
        $generator->pushCode('ob_end_clean()');
        $generator->pushCode('return $_result_');
        $generator->doEnd();
        $this->storeGeneratedCode( $generator->getResult() );
    }

    private function storeGeneratedCode($code)
    {
        $fp = @fopen($this->_codeFile, "w");
        if (!$fp) {
            throw new Exception($php_errormsg);
        }
        fwrite($fp, $code);
        fclose($fp);
    }

    private function findTemplate()
    {
        $path = $this->_realPath;
        if (file_exists($path)) return;
        foreach ($this->_repositories as $repository){
            if (file_exists("$repository/$this->_realPath")){
                $this->_realPath = "$repository/$this->_realPath";
                return;
            }
        }
        $err = 'Unable to locate template file %s';
        $err = sprintf($err, $this->_realPath);
        throw new Exception($err);
    }

    private $_codeFile;
    private $_realPath;
    private $_functionName;
    private $_prepared = false;
    private $_repositories = array();
    private $_docType = '';
    private $_errors = array();
    private $_repeat;
    private $_slots = array();
    private $_slotsStack = array();
    private $_encoding = 'UTF-8';    
    private $_nothrow = false;
    private $_translator = null;
    private $_outputMode = PHPTAL_XHTML;
}


function phptal_path( $base, $path, $nothrow=false )
{
    if ($path == '') 
        return $base;
    
    $parts   = split('/', $path);
    $current = array_shift($parts);
    $path    = join('/', $parts);
    
    if (is_object($base)) {
        if (method_exists($base, $current))
            return $path ? phptal_path($base->$current(), $path) : $base->$current();
        
        if (isset($base->$current)) 
            return $path ? phptal_path($base->$current, $path) : $base->$current;

        // if __get() exists, we use it 
        //   unless __isset() exists and tell us not to do so
        if (method_exists($base, '__get') && (!method_exists($base, '__isset') || $base->__isset($current))){
            $result = $base->$current;
            if (!is_null($result))
                return $path ? phptal_path($result, $path) : $result;
        }

        // variable does not exists but overload of __call exists, we assume it
        // is a method.
        if (method_exists($base, '__call'))
            return $path ? phptal_path($base->$current(), $path) : $base->$current();
        
        if ($nothrow)
            return null;
        
        throw new Exception("Path not found: $current");
    }
        
    if (is_array($base)) {
        if (array_key_exists($current, $base))
            return $path ? phptal_path($base[$current], $path) : $base[$current];

        if ($current == 'length')
            return $path ? phptal_path(count( $base ), $path) : count($base);

        if ($current == 'size')
            return $path ? phptal_path(count( $base ), $path) : count($base);

        if ($nothrow)
            return null;

        throw new Exception("Path not found: $current");
    }

    if (is_string($base)) {
        if ($current == 'length' || $current == 'size')
            return $path ? phptal_path(strlen($base), $path) : strlen($base);

        if ($nothrow)
            return null;

        throw new Exception("Path not found: $current");
    }

    if ($nothrow)
        return null;
    
    throw new Exception("Path not found: $current");
}

function phptal_exists( $tpl, $path )
{
    // special note: this method may requires to be extended to a full
    // phptal_path() sibling to avoid calling latest path part if it is a
    // method or a function...
    $tpl->noThrow(true);
    $res = phptal_path($tpl, $path, true);
    $tpl->noThrow(false);
    return !is_null($res);
}

?>
