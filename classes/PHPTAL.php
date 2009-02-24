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

define('PHPTAL_VERSION', '1_1_16');

//{{{PHPTAL_DIR
if (!defined('PHPTAL_DIR')) define('PHPTAL_DIR',dirname(__FILE__).DIRECTORY_SEPARATOR);
else assert('substr(PHPTAL_DIR,-1) == DIRECTORY_SEPARATOR');
//}}}

/* Please don't use the following constants. They have been replaced by methods in the PHPTAL class and are kept for backwards compatibility only. */
//{{{
if (!defined('PHPTAL_PHP_CODE_DESTINATION')) {
    if (function_exists('sys_get_temp_dir')) define('PHPTAL_PHP_CODE_DESTINATION',rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR);
    else if (substr(PHP_OS,0,3) == 'WIN') {
        if (file_exists('c:\\WINNT\\Temp\\')) define('PHPTAL_PHP_CODE_DESTINATION', 'c:\\WINNT\\Temp\\');
        else define('PHPTAL_PHP_CODE_DESTINATION', 'c:\\WINDOWS\\Temp\\');
    }
    else define('PHPTAL_PHP_CODE_DESTINATION', '/tmp/');
}
if (!defined('PHPTAL_DEFAULT_ENCODING')) define('PHPTAL_DEFAULT_ENCODING', 'UTF-8');
if (!defined('PHPTAL_PHP_CODE_EXTENSION')) define('PHPTAL_PHP_CODE_EXTENSION', 'php');
//}}}

define('PHPTAL_XHTML', 1);
define('PHPTAL_XML',   2);

require_once PHPTAL_DIR.'PHPTAL/FileSource.php';
require_once PHPTAL_DIR.'PHPTAL/RepeatController.php';
require_once PHPTAL_DIR.'PHPTAL/Context.php';
require_once PHPTAL_DIR.'PHPTAL/Exception.php';
require_once PHPTAL_DIR.'PHPTAL/TalesRegistry.php';
require_once PHPTAL_DIR.'PHPTAL/Filter.php';

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
    {
        $this->_path = $path;
        $this->_repositories = array();
        if (defined('PHPTAL_TEMPLATE_REPOSITORY')){
            $this->_repositories[] = PHPTAL_TEMPLATE_REPOSITORY;
        }
        $this->_resolvers = array();
        $this->_globalContext = new StdClass();
        $this->_context = new PHPTAL_Context();
        $this->_context->setGlobal($this->_globalContext);
    }

    /**
     * create
     * returns a new PHPTAL object
     *
     * @param string $path Template file path.
     * @return PHPTAL
     */
    public static function create($path=false)
    {
        return new PHPTAL($path);
    }

    /**
     * Clone template state and context.
     */
    public function __clone()
    {
        $context = $this->_context;
        $this->_context = clone $this->_context;
        $this->_context->setParent($context);
        $this->_context->setGlobal($this->_globalContext);
    }

    /**
     * Set template from file path.
     * @param $path string
     */
    public function setTemplate($path)
    {
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_path = $path;
        $this->_source = null;
        return $this;
    }

    /**
     * Set template from source.
     *
     * Should be used only with temporary template sources. Use setTemplate() whenever possible.
     *
     * @param $src string The phptal template source.
     * @param path string Fake and 'unique' template path.
     */
    public function setSource($src, $path=false)
    {
        if ($path == false)
            $path = '<string> '.md5($src);

        require_once PHPTAL_DIR.'PHPTAL/StringSource.php';
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_source = new PHPTAL_StringSource($src, $path);
        $this->_path = $path;
        return $this;
    }

    /**
     * Specify where to look for templates.
     *
     * @param $rep mixed string or Array of repositories
     */
    public function setTemplateRepository($rep)
    {
        if (is_array($rep)){
            $this->_repositories = $rep;
        }
        else {
            $this->_repositories[] = $rep;
        }
        return $this;
    }

    /**
     * Get template repositories.
     */
    public function getTemplateRepositories()
    {
        return $this->_repositories;
    }

    /**
     * Clears the template repositories.
     */
    public function clearTemplateRepositories()
    {
        $this->_repositories = array();
        return $this;
    }

    /**
     * Ignore XML/XHTML comments on parsing.
     * @param $bool bool
     */
    public function stripComments($bool)
    {
        $this->_stripComments = $bool;
        return $this;
    }

    /**
     * Set output mode
     * XHTML output mode will force elements like <link/>, <meta/> and <img/>, etc. to be empty
     * and threats attributes like selected, checked to be boolean attributes.
     *
     * XML output mode outputs XML without such modifications and is neccessary to generate RSS feeds properly.
     * @param $mode int (PHPTAL::XML or PHPTAL::XHTML).
     */
    public function setOutputMode($mode=PHPTAL_XHTML)
    {
        if ($mode != PHPTAL::XHTML && $mode != PHPTAL::XML){
            throw new PHPTAL_ConfigurationException('Unsupported output mode '.$mode);
        }
        $this->_outputMode = $mode;
        return $this;
    }

    /**
     * Get output mode
     */
    public function getOutputMode()
    {
        return $this->_outputMode;
    }

    /**
     * Set input and ouput encoding.
     * @param $enc string example: 'UTF-8'
     */
    public function setEncoding($enc)
    {
        $this->_encoding = $enc;
        if ($this->_translator) $this->_translator->setEncoding($enc);
        return $this;
    }

    /**
     * Get input and ouput encoding.
     * @param $enc string example: 'UTF-8'
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * Set the storage location for intermediate PHP files. The path cannot contain characters that would be interpreted by glob() (e.g. * or ?)
     * @param string $path Intermediate file path.
     */
    public function setPhpCodeDestination($path)
    {
        $this->_phpCodeDestination = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        return $this;
    }

    /**
     * Get the storage location for intermediate PHP files.
     */
    public function getPhpCodeDestination()
    {
        return $this->_phpCodeDestination;
    }

    /**
     * Set the file extension for intermediate PHP files.
     * @param string $extension The file extension.
     */
    public function setPhpCodeExtension($extension)
    {
        $this->_phpCodeExtension = $extension;
        return $this;
    }

    /**
     * Get the file extension for intermediate PHP files.
     */
    public function getPhpCodeExtension()
    {
        return $this->_phpCodeExtension;
    }

    /**
     * Flags whether to ignore intermediate php files and to
     * reparse templates every time (if set to true).
     * Don't use in production - this makes PHPTAL significantly slower.
     * @param bool bool Forced reparse state.
     */
    public function setForceReparse($bool)
    {
        $this->_forceReparse = (bool) $bool;
        return $this;
    }

    /**
     * Get the value of the force reparse state.
     */
    public function getForceReparse()
    {
        return $this->_forceReparse !== NULL ? $this->_forceReparse : (defined('PHPTAL_FORCE_REPARSE') && PHPTAL_FORCE_REPARSE);
    }

    /**
     * Set I18N translator.
     * This sets encoding used by the translator, so be sure to use encoding-dependent features of the translator (e.g. addDomain) _after_ calling setTranslator. 
     */
    public function setTranslator(PHPTAL_TranslationService $t)
    {
        $this->_translator = $t;
        $this->_translator->setEncoding($this->getEncoding());
        return $this;
    }

    /**
     * Set template pre filter. It will be called once before template is compiled.
     */
    public function setPreFilter(PHPTAL_Filter $filter)
    {
        $this->_prepared = false;
        $this->_functionName = null;        
        $this->_prefilter = $filter;
        return $this;
    }

    /**
     * Set template post filter. It will be called every time after template generates output.
     */
    public function setPostFilter(PHPTAL_Filter $filter)
    {
        $this->_postfilter = $filter;
        return $this;
    }

    /**
     * Register a trigger for specified phptal:id.
     * @param $id string phptal:id to look for
     */
    public function addTrigger($id, PHPTAL_Trigger $trigger)
    {
        $this->_triggers[$id] = $trigger;
        return $this;
    }

    /**
     * Returns trigger for specified phptal:id.
     * @param $id string phptal:id
     */
    public function getTrigger($id)
    {
        if (array_key_exists($id, $this->_triggers)){
            return $this->_triggers[$id];
        }
        return null;
    }

    /**
     * Set a context variable.
     * @param $varname string
     * @param $value mixed
     */
    public function __set($varname, $value)
    {
        $this->_context->__set($varname, $value);
    }

    /**
     * Set a context variable.
     * @param $varname string
     * @param $value mixed
     */
    public function set($varname, $value)
    {
        $this->_context->__set($varname, $value);
        return $this;
    }

    /**
     * Execute the template code.
     *
     * @return string
     */
    public function execute()
    {
        if (!$this->_prepared) {
            // includes generated template PHP code
            $this->prepare();
        }

        $this->_context->__file = $this->__file;

        $templateFunction = $this->getFunctionName();
        try {
            ob_start();
            $templateFunction($this, $this->_context);
            $res = ob_get_clean();
        }
        catch (Exception $e)
        {
            if ($e instanceof PHPTAL_TemplateException) $e->hintSrcPosition($this->_context->__file,$this->_context->__line);
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
    }

    protected function setConfigurationFrom(PHPTAL $from)
    {
        // use references - this way config of both objects will be more-or-less in sync
        $this->_encoding = &$from->_encoding;
        $this->_outputMode = &$from->_outputMode;
        $this->_stripComments = &$from->_stripComments;
        $this->_forceReparse = &$from->_forceReparse;
        $this->_phpCodeDestination = &$from->_phpCodeDestination;
        $this->_phpCodeExtension = &$from->_phpCodeExtension;
        $this->_cacheLifetime = &$from->_cacheLifetime;
        $this->_cachePurgeFrequency = &$from->_cachePurgeFrequency;
        $this->setTemplateRepository($from->_repositories);
        array_unshift($this->_repositories, dirname($from->_source->getRealPath()));
        $this->_resolvers = &$from->_resolvers;
        $this->_prefilter = &$from->_prefilter;
        $this->_postfilter = &$from->_postfilter;
    }

    private $externalMacroTempaltesCache = array();

    /**
     * Execute a template macro.
     * Should be used only from within generated template code!
     *
     * @param $path string Template macro path
     */
    public function executeMacro($path)
    {
        $this->_executeMacroOfTempalte($path, $this);
    }
    
    /**
     * This is PHPTAL's internal function that handles execution of macros from templates.
     *
     * $this is caller's context (the file where execution had originally started)
     * @param $local_tpl is PHPTAL instance of the file in which macro is defined (it will be different from $this if it's external macro call)
     * @access private
     */
    public function _executeMacroOfTempalte($path, PHPTAL $local_tpl)
    {
        // extract macro source file from macro name, if macro path does not contain filename, 
        // then the macro is assumed to be local
        if (preg_match('/^(.*?)\/([a-z0-9_-]*)$/i', $path, $m))
        {
            list(,$file,$macroName) = $m;

            if (isset($this->externalMacroTempaltesCache[$file]))
            {
                $tpl = $this->externalMacroTempaltesCache[$file];
            }
            else
            {
                $tpl = new PHPTAL($file);
                $tpl->setConfigurationFrom($this);
                $tpl->prepare();
                
                if (count($this->externalMacroTempaltesCache) > 10) $this->externalMacroTempaltesCache = array(); // keep it small (typically only 1 or 2 external files are used)
                $this->externalMacroTempaltesCache[$file] = $tpl;
            }

            // save current file
            $currentFile = $this->_context->__file;
            $this->_context->__file = $tpl->__file;

            $fun = $tpl->getFunctionName() . '_' . strtr($macroName,"-","_");
            if (!function_exists($fun)) throw new PHPTAL_MacroMissingException("Macro '$macroName' is not defined in $file",$this->_source->getRealPath());
            try
            {
                $fun($tpl, $this);
            }
            catch(PHPTAL_TemplateException $e)
            {
                $e->hintSrcPosition($tpl->_context->__file.'/'.$macroName,$tpl->_context->__line);                
                $this->_context->__file = $currentFile;
                throw $e;
            }

            // restore current file
            $this->_context->__file = $currentFile;
        }
        else 
        {
            // call local macro
            $fun = $local_tpl->getFunctionName() . '_' . strtr($path,"-","_");
            if (!function_exists($fun)) throw new PHPTAL_MacroMissingException("Macro '$path' is not defined",$local_tpl->_source->getRealPath());
            $fun( $local_tpl, $this);
        }
    }

    private function setCodeFile()
    {
        $this->_codeFile = $this->getPhpCodeDestination() . $this->getFunctionName() . '.' . $this->getPhpCodeExtension();
    }

    /**
     * Prepare template without executing it.
     */
    public function prepare()
    {
        // clear just in case settings changed and cache is out of date
        $this->externalMacroTempaltesCache = array();
        
        // find the template source file
        $this->findTemplate();
        $this->__file = $this->_source->getRealPath();
		$this->setCodeFile();
		
        // parse template if php generated code does not exists or template
        // source file modified since last generation of PHPTAL_FORCE_REPARSE
        // is defined.
        if ($this->getForceReparse() || !file_exists($this->getCodePath()))
		{
	        if ($this->getCachePurgeFrequency() && mt_rand()%$this->getCachePurgeFrequency() == 0)
    		{
    		    $this->cleanUpGarbage();
    		}
            $this->parse();
        }
        
        require_once $this->getCodePath();
        
        $this->_prepared = true;
        return $this;
    }

    public function getCacheLifetime()
    {
        return $this->_cacheLifetime;
    }

    /**
     * how long compiled templates and phptal:cache files are kept, in days
     */
    public function setCacheLifetime($days)
    {
        $this->_cacheLifetime = max(0.5,$days);
        return $this;
    }

    /**
     * PHPTAL will scan cache and remove old files on every nth compile
     * Set to 0 to disable cleanups
     */
    public function setCachePurgeFrequency($n)
    {
        $this->_cachePurgeFrequency = (int)$n;
        return $this;
    }

    public function getCachePurgeFrequency()
    {
        return $this->_cachePurgeFrequency;
    }


	/**
	 * Removes all compiled templates from cache after PHPTAL_CACHE_LIFETIME days
	 */
	public function cleanUpGarbage()
	{
		$phptalCacheFilesExpire = time() - $this->getCacheLifetime() * 3600 * 24;
		$upperLimit = $this->getPhpCodeDestination() . 'tpl_' . $phptalCacheFilesExpire . '_';
		$lowerLimit = $this->getPhpCodeDestination() . 'tpl_0_';
		$phptalCacheFiles = glob($this->getPhpCodeDestination() . 'tpl_*.' . $this->getPhpCodeExtension() . '*');

		if ($phptalCacheFiles)
		{
			foreach($phptalCacheFiles as $index => $file)
	        {
				if ($file > $upperLimit && substr($file,0,strlen($lowerLimit)) !== $lowerLimit)
				{
				    unset($phptalCacheFiles[$index]);
				}
	        }
	        foreach($phptalCacheFiles as $file)
	        {
	            $time = filemtime($file);
	            if ($time && $time < $phptalCacheFilesExpire) @unlink($file);			 
		    }
	    }
	}

    /**
	 * Removes single compiled template from cache and all its fragments cached by phptal:cache.
	 * Must be called after setSource/setTemplate.
	 */
	public function cleanUpCache()
	{
		if (!$this->getCodePath()) 
		{
			$this->findTemplate(); $this->setCodeFile();
			if (!$this->getCodePath()) throw new PHPTAL_ConfigurationException("No codefile");
		}
		
		$filename = $this->getCodePath();		
		$phptalCacheFiles = glob($filename . '*');
		if ($phptalCacheFiles) foreach($phptalCacheFiles as $file)
		{
		    if (substr($file, 0, strlen($filename)) !== $filename) continue; // safety net
			@unlink($file);
	    }
        $this->_prepared = false;
	}	

    /**
     * Returns the path of the intermediate PHP code file.
     *
     * The returned file may be used to cleanup (unlink) temporary files
     * generated by temporary templates or more simply for debug.
     *
     * @return string
     */
    public function getCodePath()
    {
        return $this->_codeFile;
    }

    /**
     * Returns the generated template function name.
     * @return string
     */
    public function getFunctionName()
    {
        if (!$this->_functionName) 
        {   
            // function name is used as base for caching, so it must be unique for every combination of settings
            // that changes code in compiled template         
            $this->_functionName = 'tpl_' . $this->_source->getLastModifiedTime() . '_' . PHPTAL_VERSION .
                substr(preg_replace('/[^a-zA-Z]/','_',basename($this->_source->getRealPath())),0,15) . 
                md5($this->_source->getRealPath() . ($this->_prefilter ? get_class($this->_prefilter) : '-'));
        }
        return $this->_functionName;
    }

    /**
     * Returns template translator.
     * @return PHPTAL_TranslationService
     */
    public function getTranslator()
    {
        return $this->_translator;
    }

    /**
     * Returns array of exceptions caught by tal:on-error attribute.
     * @return array<Exception>
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * Public for phptal templates, private for user.
     * @access private
     */
    public function addError(Exception $error)
    {
        array_push($this->_errors, $error);
        return $this;
    }

    /**
     * Returns current context object.
     * Use only in Triggers.
     *
     * @return PHPTAL_Context
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * only for use in generated template code
     * @access private
     */
    public function getGlobalContext()
    {
        return $this->_globalContext;
    }

    /**
     * only for use in generated template code
     * @access private
     */
    public function pushContext()
    {
        $this->_context = $this->_context->pushContext();
        return $this->_context;
    }

    /**
     * only for use in generated template code
     * @access private
     */
    public function popContext()
    {
        $this->_context = $this->_context->popContext();
        return $this->_context;
    }

    protected function parse()
    {
        require_once PHPTAL_DIR.'PHPTAL/Dom/Parser.php';

        // instantiate the PHPTAL source parser
        $parser = new PHPTAL_Dom_Parser($this->_encoding);
        $parser->stripComments($this->_stripComments);

        $data = $this->_source->getData();
        $realpath = $this->_source->getRealPath();

        if ($this->_prefilter)
            $data = $this->_prefilter->filter($data);
        $tree = $parser->parseString($data, $realpath);

        require_once PHPTAL_DIR.'PHPTAL/Php/CodeGenerator.php';
        $generator = new PHPTAL_Php_CodeGenerator($this->getFunctionName(), $this->_source->getRealPath());
        $generator->setEncoding($this->_encoding);
        $generator->setOutputMode($this->_outputMode);
        $generator->generate($tree);

        if (!@file_put_contents($this->getCodePath(), $generator->getResult())) {
            throw new PHPTAL_IOException('Unable to open '.$this->getCodePath().' for writing');
        }

        return $this;
    }

    /**
     * Search template source location.
     */
    protected function findTemplate()
    {
        if ($this->_path == false){
            throw new PHPTAL_ConfigurationException('No template file specified');
        }

        // template source already defined
        if ($this->_source != null){
            return;
        }

        array_push($this->_resolvers, new PHPTAL_FileSourceResolver($this->_repositories));
        foreach ($this->_resolvers as $resolver){
            $source = $resolver->resolve($this->_path);
            if ($source != null){
                $this->_source = $source;
                break;
            }
        }
        array_pop($this->_resolvers);

        if ($this->_source == null){
            throw new PHPTAL_IOException('Unable to locate template file '.$this->_path);
        }
    }

    protected $_prefilter = null;
    protected $_postfilter = null;

    // list of template source repositories
    protected $_repositories = array();
    // template path
    protected $_path = null;
    // template source resolvers
    protected $_resolvers = array();
    // template source (only set when not working with file)
    protected $_source = null;
    // destination of PHP intermediate file
    protected $_codeFile = null;
    // php function generated for the template
    protected $_functionName = null;
    // set to true when template is ready for execution
    protected $_prepared = false;

    // associative array of phptal:id => PHPTAL_Trigger
    protected $_triggers = array();
    // i18n translator
    protected $_translator = null;

    // global execution context
    protected $_globalContext = null;
    // current execution context
    protected $_context = null;
    // current template file (changes within macros)
    public  $__file = false;
    // list of on-error caught exceptions
    protected $_errors = array();

    protected $_encoding = PHPTAL_DEFAULT_ENCODING;
    protected $_outputMode = PHPTAL::XHTML;
    protected $_stripComments = false;

    // configuration properties
    protected $_forceReparse = NULL;
    protected $_phpCodeDestination = PHPTAL_PHP_CODE_DESTINATION;
    protected $_phpCodeExtension = PHPTAL_PHP_CODE_EXTENSION;

    protected $_cacheLifetime = 30;
    protected $_cachePurgeFrequency = 50;
}

?>
