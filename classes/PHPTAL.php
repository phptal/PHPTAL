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

define('PHPTAL_VERSION', '1_2_0a6');

if (!defined('PHPTAL_DIR')) {
    define('PHPTAL_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR);
} else {
    assert('substr(PHPTAL_DIR,-1) == DIRECTORY_SEPARATOR');
}

require PHPTAL_DIR.'PHPTAL/Source.php';
require PHPTAL_DIR.'PHPTAL/SourceResolver.php';
require PHPTAL_DIR.'PHPTAL/FileSource.php';
require PHPTAL_DIR.'PHPTAL/RepeatController.php';
require PHPTAL_DIR.'PHPTAL/Context.php';
require PHPTAL_DIR.'PHPTAL/Exception.php';
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
 */
class PHPTAL
{
    const XHTML = 111;
    const XML   = 222;
    const HTML5 = 555;

    /**
     * PHPTAL Constructor.
     *
     * @param string $path Template file path.
     */
    public function __construct($path=false)
    {
        $this->_path = $path;
        $this->_globalContext = new StdClass();
        $this->_context = new PHPTAL_Context();
        $this->_context->setGlobal($this->_globalContext);

        if (function_exists('sys_get_temp_dir')) {
            $this->setPhpCodeDestination(sys_get_temp_dir());
        } elseif (substr(PHP_OS, 0, 3) == 'WIN') {
            if (file_exists('c:\\WINNT\\Temp\\')) {
                $this->setPhpCodeDestination('c:\\WINNT\\Temp');
            } else {
                $this->setPhpCodeDestination('c:\\WINDOWS\\Temp\\');
            }
        } else {
            $this->setPhpCodeDestination('/tmp/');
        }
    }

    /**
     * create
     * returns a new PHPTAL object
     *
     * @param string $path Template file path.
     *
     * @return PHPTAL
     */
    public static function create($path=false)
    {
        return new PHPTAL($path);
    }

    /**
     * Clone template state and context.
     *
     * @return void
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
     *
     * @param string $path filesystem path, or any path that will be accepted by source resolver
     * @return $this
     */
    public function setTemplate($path)
    {
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_codeFile = null;
        $this->_path = $path;
        $this->_source = null;
        return $this;
    }

    /**
     * Set template from source.
     *
     * Should be used only with temporary template sources. Use setTemplate() whenever possible.
     *
     * @param string $src The phptal template source.
     * @param string $path Fake and 'unique' template path.
     * @return $this
     */
    public function setSource($src, $path=false)
    {
        if ($path == false) {
            $path = '<string '.md5($src).'>';
        }

        require_once PHPTAL_DIR.'PHPTAL/StringSource.php';
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_codeFile = null;
        $this->_source = new PHPTAL_StringSource($src, $path);
        $this->_path = $path;
        return $this;
    }

    /**
     * Specify where to look for templates.
     *
     * @param mixed $rep string or Array of repositories
     * @return $this
     */
    public function setTemplateRepository($rep)
    {
        if (is_array($rep)) {
            $this->_repositories = $rep;
        } else {
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
     * Specify how to look for templates.
     *
     * @param $resolver PHPTAL_SourceResolver
     * @return $this
     */
    public function addSourceResolver(PHPTAL_SourceResolver $resolver)
    {
        $this->_resolvers[] = $rep;
        return $this;
    }

    /**
     * Ignore XML/XHTML comments on parsing.
     * Comments starting with <!--! are always stripped.
     *
     * @param bool $bool
     * @return $this
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
     *
     * @param int $mode (PHPTAL::XML, PHPTAL::XHTML or PHPTAL::HTML5).
     * @return $this
     */
    public function setOutputMode($mode)
    {
        $this->_prepared = false;
        $this->_functionName = null;
        if ($mode != PHPTAL::XHTML && $mode != PHPTAL::XML && $mode != PHPTAL::HTML5) {
            throw new PHPTAL_ConfigurationException('Unsupported output mode '.$mode);
        }
        $this->_outputMode = $mode;
        return $this;
    }

    /**
     * Get output mode
     * @see setOutputMode()
     */
    public function getOutputMode()
    {
        return $this->_outputMode;
    }

    /**
     * Set input and ouput encoding.
     * @param string $enc example: 'UTF-8'
     * @return $this
     */
    public function setEncoding($enc)
    {
        $this->_encoding = $enc;
        if ($this->_translator) $this->_translator->setEncoding($enc);
        return $this;
    }

    /**
     * Get input and ouput encoding.
     * @param string $enc example: 'UTF-8'
     * @return $this
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
        $this->_functionName = null;
        $this->_codeFile = null;
        $this->_prepared = false;
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
        $this->_codeFile = null;
        $this->_prepared = false;
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
        return $this->_forceReparse !== null ? $this->_forceReparse : (defined('PHPTAL_FORCE_REPARSE') && PHPTAL_FORCE_REPARSE);
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
     * @param string $id phptal:id to look for
     */
    public function addTrigger($id, PHPTAL_Trigger $trigger)
    {
        $this->_triggers[$id] = $trigger;
        return $this;
    }

    /**
     * Returns trigger for specified phptal:id.
     * @param string $id phptal:id
     */
    public function getTrigger($id)
    {
        if (array_key_exists($id, $this->_triggers)) {
            return $this->_triggers[$id];
        }
        return null;
    }

    /**
     * Set a context variable.
     * @param string $varname
     * @param mixed $value
     */
    public function __set($varname, $value)
    {
        $this->_context->__set($varname, $value);
    }

    /**
     * Set a context variable.
     * @param string $varname
     * @param mixed $value
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

        $this->_context->_file = $this->_file;

        $templateFunction = $this->getFunctionName();
        try {
            ob_start();
            $templateFunction($this, $this->_context);
            $res = ob_get_clean();
        }
        catch (Exception $e)
        {
            if ($e instanceof PHPTAL_TemplateException) $e->hintSrcPosition($this->_context->_file, $this->_context->_line);
            ob_end_clean();
            throw $e;
        }

        // unshift doctype
        $docType = $this->_context->_docType;
        if ($docType) {
            $res = $docType . "\n" . $res;
        }
        // unshift xml declaration
        $xmlDec = $this->_context->_xmlDeclaration;
        if ($xmlDec) {
            $res = $xmlDec . "\n" . $res;
        }

        if ($this->_postfilter) {
            return $this->_postfilter->filter($res);
        }
        return $res;
    }

    /**
     * copies state of PHPTAL class. for internal use only.
     */
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
     * @param string $path Template macro path
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
        if (preg_match('/^(.*?)\/([a-z0-9_-]*)$/i', $path, $m)) {
            list(,$file, $macroName) = $m;

            if (isset($this->externalMacroTempaltesCache[$file])) {
                $tpl = $this->externalMacroTempaltesCache[$file];
            } else {
                $tpl = new PHPTAL($file);
                $tpl->setConfigurationFrom($this);
                $tpl->prepare();

                if (count($this->externalMacroTempaltesCache) > 10) $this->externalMacroTempaltesCache = array(); // keep it small (typically only 1 or 2 external files are used)
                $this->externalMacroTempaltesCache[$file] = $tpl;
            }

            // save current file
            $currentFile = $this->_context->_file;
            $this->_context->_file = $tpl->_file;

            $fun = $tpl->getFunctionName() . '_' . strtr($macroName,"-","_");
            if (!function_exists($fun)) throw new PHPTAL_MacroMissingException("Macro '$macroName' is not defined in $file", $this->_source->getRealPath());
            try
            {
                $fun($tpl, $this);
            }
            catch(PHPTAL_TemplateException $e)
            {
                $e->hintSrcPosition($tpl->_context->_file.'/'.$macroName, $tpl->_context->_line);
                $this->_context->_file = $currentFile;
                throw $e;
            }

            // restore current file
            $this->_context->_file = $currentFile;
        } else {
            // call local macro
            $fun = $local_tpl->getFunctionName() . '_' . strtr($path,"-","_");
            if (!function_exists($fun)) throw new PHPTAL_MacroMissingException("Macro '$path' is not defined", $local_tpl->_source->getRealPath());
            $fun( $local_tpl, $this);
        }
    }

    /**
     * ensure that getCodePath will return up-to-date path
     */
    private function setCodeFile()
    {
        $this->findTemplate();
        $this->_codeFile = $this->getPhpCodeDestination() . $this->getFunctionName() . '.' . $this->getPhpCodeExtension();
    }

    /**
     * Prepare template without executing it.
     */
    public function prepare()
    {
        // clear just in case settings changed and cache is out of date
        $this->externalMacroTempaltesCache = array();

        // find the template source file and update function name
        $this->setCodeFile();
        $this->_file = $this->_source->getRealPath();

        if (!function_exists($this->getFunctionName())) {
            // parse template if php generated code does not exists or template
            // source file modified since last generation of PHPTAL_FORCE_REPARSE
            // is defined.
            if ($this->getForceReparse() || !file_exists($this->getCodePath())) {
                if ($this->getCachePurgeFrequency() && mt_rand()%$this->getCachePurgeFrequency() == 0) {
                    $this->cleanUpGarbage();
                }

                $result = $this->parse();

                if (!$this->getForceReparse()) {
                    if (!file_put_contents($this->getCodePath(), $result)) {
                        throw new PHPTAL_IOException('Unable to open '.$this->getCodePath().' for writing');
                    }
                }

                // the awesome thing about eval() is that parse errors don't stop PHP.
                ob_start();
                try {
                    eval('?>'.$result);                    
                }
                catch(Exception $e) {
                    ob_end_clean();
                    // save file if it wasn't saved already - this is needed for debugging
                    if ($this->getForceReparse()) @file_put_contents($this->getCodePath(), $result);
                    throw $e;
                }
                if (!function_exists($this->getFunctionName())) {
                    $msg = str_replace("eval()'d code", $this->getCodePath(), ob_get_clean());

                    // save file if it wasn't saved already
                    if ($this->getForceReparse()) @file_put_contents($this->getCodePath(), $result); 

                    if (preg_match('/on line (\d+)$/m', $msg, $m)) $line =$m[1]; else $line=0;
                    throw new PHPTAL_TemplateException($msg, $this->getCodePath(), $line);
                }
                ob_end_clean();
                
            } else {
                require $this->getCodePath();
            }
        }

        $this->_prepared = true;
        return $this;
    }

    /**
     * get how long compiled templates and phptal:cache files are kept, in days
     */
    public function getCacheLifetime()
    {
        return $this->_cacheLifetime;
    }

    /**
     * set how long compiled templates and phptal:cache files are kept
     * @param $days number of days
     */
    public function setCacheLifetime($days)
    {
        $this->_cacheLifetime = max(0.5, $days);
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

    /**
     * how likely cache cleaning can happen
     * @see self::setCachePurgeFrequency()
     */
    public function getCachePurgeFrequency()
    {
        return $this->_cachePurgeFrequency;
    }


    /**
     * Removes all compiled templates from cache that are older than getCacheLifetime() days
     */
    public function cleanUpGarbage()
    {
        $phptalCacheFilesExpire = time() - $this->getCacheLifetime() * 3600 * 24;
        $upperLimit = $this->getPhpCodeDestination() . 'tpl_' . $phptalCacheFilesExpire . '_';
        $lowerLimit = $this->getPhpCodeDestination() . 'tpl_0_';
        $phptalCacheFiles = glob($this->getPhpCodeDestination() . 'tpl_*.' . $this->getPhpCodeExtension() . '*');

        if ($phptalCacheFiles) {
            foreach ($phptalCacheFiles as $index => $file) {
                if ($file > $upperLimit && substr($file, 0, strlen($lowerLimit)) !== $lowerLimit) {
                    unset($phptalCacheFiles[$index]);
                }
            }
            foreach ($phptalCacheFiles as $file) {
                $time = filemtime($file);
                if ($time && $time < $phptalCacheFilesExpire) @unlink($file);
            }
        }
    }

    /**
     * Removes content cached with phptal:cache for currently set template
     * Must be called after setSource/setTemplate.
     */
    public function cleanUpCache()
    {
        $filename = $this->getCodePath();
        $phptalCacheFiles = glob($filename . '?*');
        if ($phptalCacheFiles) foreach ($phptalCacheFiles as $file) {
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
        if (!$this->_codeFile) $this->setCodeFile();
        return $this->_codeFile;
    }

    /**
     * Returns the generated template function name.
     * @return string
     */
    public function getFunctionName()
    {
        if (!$this->_functionName) {
            // function name is used as base for caching, so it must be unique for every combination of settings
            // that changes code in compiled template
            $this->_functionName = 'tpl_' . $this->_source->getLastModifiedTime() . '_' . PHPTAL_VERSION .
                substr(preg_replace('/[^a-zA-Z]/', '_',basename($this->_source->getRealPath())), 0,15) .
                md5($this->_source->getRealPath() . ($this->_prefilter ? get_class($this->_prefilter) : '-') . $this->getOutputMode());
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
        $this->_errors[] =  $error;
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

    /**
     * parse currently set template
     * @return string (compiled PHP code)
     */
    protected function parse()
    {
        require_once PHPTAL_DIR.'PHPTAL/Dom/DocumentBuilder.php';
        require_once PHPTAL_DIR.'PHPTAL/Php/CodeGenerator.php';

        // instantiate the PHPTAL source parser
        $parser = new PHPTAL_XmlParser($this->_encoding);
        $builder = new PHPTAL_DOM_DocumentBuilder();
        $builder->stripComments($this->_stripComments);

        $data = $this->_source->getData();
        $realpath = $this->_source->getRealPath();

        if ($this->_prefilter)
            $data = $this->_prefilter->filter($data);
        $tree = $parser->parseString($builder, $data, $realpath)->getResult();

        $generator = new PHPTAL_Php_CodeGenerator($this->getFunctionName(), $this->_source->getRealPath(), $this->_encoding, $this->_outputMode, $this->getCodePath());
        $result = $generator->generateCode($tree);

        return $result;
    }

    /**
     * Search template source location.
     * @return void
     */
    protected function findTemplate()
    {
        if ($this->_path == false) {
            throw new PHPTAL_ConfigurationException('No template file specified');
        }

        // template source already defined
        if ($this->_source) {
            return;
        }

        foreach ($this->_resolvers as $resolver) {
            $source = $resolver->resolve($this->_path);
            if ($source) {
                $this->_source = $source;
                return;
            }
        }

        $resolver = new PHPTAL_FileSourceResolver($this->_repositories);
        $this->_source = $resolver->resolve($this->_path);

        if (!$this->_source) {
            throw new PHPTAL_IOException('Unable to locate template file '.$this->_path);
        }
    }

    protected $_prefilter = null;
    protected $_postfilter = null;

    /**
     *  list of template source repositories
     */
    protected $_repositories = array();
    /**
     *  template path
     */
    protected $_path = null;
    /**
     *  template source resolvers
     */
    protected $_resolvers = array();
    /**
     *  template source (only set when not working with file)
     */
    protected $_source = null;
    /**
     * destination of PHP intermediate file
     */
    protected $_codeFile = null;
    /**
     * php function generated for the template
     */
    protected $_functionName = null;
    /**
     * set to true when template is ready for execution
     */
    protected $_prepared = false;

    /**
     * associative array of phptal:id => PHPTAL_Trigger
     */
    protected $_triggers = array();
    /**
     * i18n translator
     */
    protected $_translator = null;

    /**
     * global execution context
     */
    protected $_globalContext = null;
    /**
     * current execution context
     */
    protected $_context = null;
    /**
     * current template file (changes within macros)
     */
    public  $_file = false;
    /**
     * list of on-error caught exceptions
     */
    protected $_errors = array();

    /**
     * encoding used throughout
     */
    protected $_encoding = 'UTF-8';

    /**
     * type of syntax used in generated templates
     */
    protected $_outputMode = PHPTAL::XHTML;
    /**
     * should all comments be stripped
     */
    protected $_stripComments = false;

    // configuration properties

    /**
     * don't use code cache
     */
    protected $_forceReparse = null;

    /**
     * directory where code cache is
     */
    protected $_phpCodeDestination;
    protected $_phpCodeExtension = 'php';

    /**
     * number of days
     */
    protected $_cacheLifetime = 30;

    /**
     * 1/x
     */
    protected $_cachePurgeFrequency = 50;
}

