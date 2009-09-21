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
 * @link     http://phptal.org/
 */

define('PHPTAL_VERSION', '1_2_1b2');


/* If you want to use autoload, comment out all lines starting with require_once 'PHPTAL
   and uncomment the line below: */

// spl_autoload_register(array('PHPTAL','autoload'));


PHPTAL::setIncludePath();
require_once 'PHPTAL/Source.php';
require_once 'PHPTAL/SourceResolver.php';
require_once 'PHPTAL/FileSource.php';
require_once 'PHPTAL/RepeatController.php';
require_once 'PHPTAL/Context.php';
require_once 'PHPTAL/Exception.php';
require_once 'PHPTAL/Filter.php';
PHPTAL::restoreIncludePath();

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
    //{{{
    /**
     * constants for output mode
     * @see setOutputMode()
     */
    const XHTML = 11;
    const XML   = 22;
    const HTML5 = 55;

    protected $_prefilters = array();

    /**
     * @deprecated
     */
    private $_prefilter = 'REMOVED: DO NOT USE';
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
    protected $_cachePurgeFrequency = 30;

    /**
     * speeds up calls to external templates
     */
    private $externalMacroTemplatesCache = array();

    /**
     * restore_include_path() resets path to default in ini,
     * breaking application's custom paths, so a custom backup is necessary.
     */
    private static $include_path_backup;

    /**
     * keeps track of multiple calls to setIncludePath()
     */
    private static $include_path_set_nesting = 0;
    //}}}

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
        $this->_context->_docType = null;
        $this->_context->_xmlDeclaration = null;
        return $this;
    }

    /**
     * Set template from source.
     *
     * Should be used only with temporary template sources.
     * Use setTemplate() or addSourceResolver() whenever possible.
     *
     * @param string $src The phptal template source.
     * @param string $path Fake and 'unique' template path.
     * @return $this
     */
    public function setSource($src, $path=false)
    {
        PHPTAL::setIncludePath();
        require_once 'PHPTAL/StringSource.php';
        PHPTAL::restoreIncludePath();

        if (!$path) {
            $path = PHPTAL_StringSource::NO_PATH_PREFIX.md5($src).'>';
        }

        $this->_prepared = false;
        $this->_functionName = null;
        $this->_codeFile = null;
        $this->_source = new PHPTAL_StringSource($src, $path);
        $this->_path = $path;
        $this->_context->_docType = null;
        $this->_context->_xmlDeclaration = null;
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
        $this->_resolvers[] = $resolver;
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
     * XHTML output mode will force elements like <link/>, <meta/> and <img/>, etc.
     * to be empty and threats attributes like selected, checked to be
     * boolean attributes.
     *
     * XML output mode outputs XML without such modifications
     * and is neccessary to generate RSS feeds properly.
     *
     * @param int $mode (PHPTAL::XML, PHPTAL::XHTML or PHPTAL::HTML5).
     * @return $this
     */
    public function setOutputMode($mode)
    {
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_codeFile = null;
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
        if ($enc != $this->_encoding) {
            $this->_encoding = $enc;
            if ($this->_translator) $this->_translator->setEncoding($enc);

            $this->_prepared = false;
            $this->_functionName = null;
            $this->_codeFile = null;
        }
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
     * Set the storage location for intermediate PHP files.
     * The path cannot contain characters that would be interpreted by glob() (e.g. *[]?)
     *
     * @param string $path Intermediate file path.
     */
    public function setPhpCodeDestination($path)
    {
        $this->_phpCodeDestination = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
     *
     * DON'T USE IN PRODUCTION - this makes PHPTAL many times slower.
     *
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
        return $this->_forceReparse;
    }

    /**
     * Set I18N translator.
     *
     * This sets encoding used by the translator, so be sure to use encoding-dependent
     * features of the translator (e.g. addDomain) _after_ calling setTranslator.
     */
    public function setTranslator(PHPTAL_TranslationService $t)
    {
        $this->_translator = $t;
        $t->setEncoding($this->getEncoding());
        return $this;
    }


    /**
     * Set template pre filter. It will be called once before template is compiled.
     *
     * Please use addPreFilter instead
     *
     * @see addPreFilter
     * @deprecated
     */
    final public function setPreFilter(PHPTAL_Filter $filter)
    {
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_codeFile = null;

        $this->_prefilters['_phptal_old_filter_'] = $filter;
    }

    /**
     * Add new prefilter to filter chain. Filter must extend PHPTAL_PreFilter class.
     * 
     * If you specify $key, prefilter will be added under specific key. 
     * Adding new prefilter with same name will replace it instead.
     * 
     * @param PHPTAL_PreFilter $filter filter to add
     * @param string $key name for filter or NULL. Must not be numeric.
     * @return PHPTAL
     */
    final public function addPreFilter(PHPTAL_PreFilter $filter, $key = NULL)
    {
        $this->_prepared = false;
        $this->_functionName = null;
        $this->_codeFile = null;

        if ($key !== NULL) {
            if (is_numeric($key)) {
                throw new PHPTAL_ConfigurationException("Key for prefilter must not be non-numeric string");
            }
            $this->_prefilters[$key] = $filter;
        } else {
            $this->_prefilters[] = $filter;
        }
        return $this;
    }

    /**
     * Array with all prefilter objects
     *
     * @return array
     */
    protected function getPreFilters()
    {
        return $this->_prefilters;
    }

    /**
     * Return string that is unique for every different configuration of prefilters.
     * Result of prefilters may be cached unless this string changes.
     *
     * @return string
     */
    protected function getPreFiltersCacheId()
    {
        $cacheid = '';
        foreach($this->getPreFilters() as $key => $prefilter) {
            if ($prefilter instanceof PHPTAL_PreFilter) {
                $cacheid .= $key.$prefilter->getCacheId();
            } else {
                $cacheid .= $key.get_class($prefilter);
            }
        }
        return $cacheid;
    }

    /**
     * Set template post filter.
     * It will be called every time after template generates output.
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
     * Use it by setting properties on PHPTAL object.
     *
     * @param string $varname
     * @param mixed $value
     * @return void
     */
    public function __set($varname, $value)
    {
        $this->_context->__set($varname, $value);
    }

    /**
     * Set a context variable.
     *
     * @see PHPTAL::__set()
     * @param string $varname name of the variable
     * @param mixed $value value of the variable
     * @return $this
     */
    public function set($varname, $value)
    {
        $this->_context->__set($varname, $value);
        return $this;
    }

    /**
     * Execute the template code and return generated markup.
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
        $this->_context->echoDeclarations(false);

        $templateFunction = $this->getFunctionName();
        try {
            ob_start();
            $templateFunction($this, $this->_context);
            $res = ob_get_clean();
        }
        catch (Exception $e)
        {
            ob_end_clean();
            if ($e instanceof PHPTAL_TemplateException) {
                $e->hintSrcPosition($this->_context->_file, $this->_context->_line);
            }
            throw $e;
        }

        // unshift doctype
        if ($this->_context->_docType) {
            $res = $this->_context->_docType . "\n" . $res;
        }

        // unshift xml declaration
        if ($this->_context->_xmlDeclaration) {
            $res = $this->_context->_xmlDeclaration . "\n" . $res;
        }

        if ($this->_postfilter) {
            return $this->_postfilter->filter($res);
        }
        return $res;
    }

    /**
     * Execute and echo template without buffering of the output.
     * This function does not allow postfilters nor DOCTYPE/XML declaration.
     *
     * @return NULL
     */
    public function echoExecute()
    {
        if (!$this->_prepared) {
            // includes generated template PHP code
            $this->prepare();
        }

        if ($this->_postfilter) {
            throw new PHPTAL_ConfigurationException("echoExecute() does not support postfilters");
        }

        $this->_context->_file = $this->_file;
        $this->_context->echoDeclarations(true);

        $templateFunction = $this->getFunctionName();
        try {
            $templateFunction($this, $this->_context);
        }
        catch (PHPTAL_TemplateException $e)
        {
            $e->hintSrcPosition($this->_context->_file, $this->_context->_line);
            throw $e;
        }
    }

    /**
     * Execute a template macro.
     * Should be used only from within generated template code!
     *
     * @param string $path Template macro path
     */
    public function executeMacro($path)
    {
        $this->_executeMacroOfTemplate($path, $this);
    }

    /**
     * This is PHPTAL's internal function that handles
     * execution of macros from templates.
     *
     * $this is caller's context (the file where execution had originally started)
     * @param $local_tpl is PHPTAL instance of the file in which macro is defined (it will be different from $this if it's external macro call)
     * @access private
     */
    final public function _executeMacroOfTemplate($path, PHPTAL $local_tpl)
    {
        // extract macro source file from macro name, if macro path does not
        // contain filename, then the macro is assumed to be local

        if (preg_match('/^(.*?)\/([a-z0-9_-]*)$/i', $path, $m)) {
            list(,$file, $macroName) = $m;

            if (isset($this->externalMacroTemplatesCache[$file])) {
                $tpl = $this->externalMacroTemplatesCache[$file];
            } else {
                $tpl = clone $this;
                array_unshift($tpl->_repositories, dirname($this->_source->getRealPath()));
                $tpl->setTemplate($file);
                $tpl->prepare();

                // keep it small (typically only 1 or 2 external files are used)
                if (count($this->externalMacroTemplatesCache) > 10) {
                    $this->externalMacroTemplatesCache = array();
                }
                $this->externalMacroTemplatesCache[$file] = $tpl;
            }

            // save current file
            $currentFile = $this->_context->_file;
            $this->_context->_file = $tpl->_file;

            $fun = $tpl->getFunctionName() . '_' . strtr($macroName,"-","_");
            if (!function_exists($fun)) {
                throw new PHPTAL_MacroMissingException("Macro '$macroName' is not defined in $file", $this->_source->getRealPath());
            }
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
            if (!function_exists($fun)) {
                throw new PHPTAL_MacroMissingException("Macro '$path' is not defined", $local_tpl->_source->getRealPath());
            }
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
        $this->externalMacroTemplatesCache = array();

        // find the template source file and update function name
        $this->setCodeFile();
        $this->_file = $this->_source->getRealPath();

        if (!function_exists($this->getFunctionName())) {
            // parse template if php generated code does not exists or template
            // source file modified since last generation or force reparse is set
            if ($this->getForceReparse() || !file_exists($this->getCodePath())) {

                // i'm not sure where that belongs, but not in normal path of execution
                // because some sites have _a lot_ of files in temp
                if ($this->getCachePurgeFrequency() && mt_rand()%$this->getCachePurgeFrequency() == 0) {
                    $this->cleanUpGarbage();
                }

                $result = $this->parse();

                if (!file_put_contents($this->getCodePath(), $result)) {
                    throw new PHPTAL_IOException('Unable to open '.$this->getCodePath().' for writing');
                }

                // the awesome thing about eval() is that parse errors don't stop PHP.
                // when PHP dies during eval, fatal error is printed and
                // can be captured with output buffering
                ob_start();
                try {
                    eval('require $this->getCodePath();');

                    if (!function_exists($this->getFunctionName())) {
                        $msg = ob_get_contents();
                        // greedy .* ensures last match
                        if (preg_match('/.*on line (\d+)$/m', $msg, $m)) $line =$m[1]; else $line=0;
                        throw new PHPTAL_TemplateException($msg, $this->getCodePath(), $line);
                    }
                }
                catch(Exception $e) {
                    ob_end_flush();
                    throw $e;
                }
                ob_end_clean();

            } else {
                // eval trick is used only on first run,
                // just in case it causes any problems with opcode accelerators
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
     * Removes all compiled templates from cache that
     * are older than getCacheLifetime() days
     */
    public function cleanUpGarbage()
    {
        $cacheFilesExpire = time() - $this->getCacheLifetime() * 3600 * 24;

        // relies on templates sorting order being related to their modification dates
        $upperLimit = $this->getPhpCodeDestination() . $this->getFunctionNamePrefix($cacheFilesExpire) . '_';
        $lowerLimit = $this->getPhpCodeDestination() . $this->getFunctionNamePrefix(0);

        // second * gets phptal:cache
        $cacheFiles = glob($this->getPhpCodeDestination() . 'tpl_????????_*.' . $this->getPhpCodeExtension() . '*');

        if ($cacheFiles) {
            foreach ($cacheFiles as $index => $file) {

                // comparison here skips filenames that are certainly too new
                if (strcmp($file,$upperLimit) <= 0 || substr($file, 0, strlen($lowerLimit)) === $lowerLimit) {
                    $time = filemtime($file);
                    if ($time && $time < $cacheFilesExpire) {
                        @unlink($file);
                }
            }
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
        $cacheFiles = glob($filename . '?*');
        if ($cacheFiles) {
            foreach ($cacheFiles as $file) {
                if (substr($file, 0, strlen($filename)) !== $filename) continue; // safety net
                @unlink($file);
            }
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
       // function name is used as base for caching, so it must be unique for
       // every combination of settings that changes code in compiled template

       if (!$this->_functionName) {

            // just to make tempalte name recognizable
            $basename = preg_replace('/\.[a-z]{3,4}$/','',basename($this->_source->getRealPath()));
            $basename = substr(trim(preg_replace('/[^a-zA-Z0-9]+/', '_',$basename),"_"), 0,20);

            $hash = md5(PHPTAL_VERSION . $this->_source->getRealPath()
                    . $this->getEncoding()
                    . $this->getPrefiltersCacheId()
                    . $this->getOutputMode(),
                    true);

            // uses base64 rather than hex to make filename shorter.
            // there is loss of some bits due to name constraints and case-insensivity,
            // but that's still over 110 bits in addition to basename and timestamp.
            $hash = strtr(rtrim(base64_encode($hash),"="),"+/=","_A_");

            $this->_functionName = $this->getFunctionNamePrefix($this->_source->getLastModifiedTime()) .
                                   $basename . '__' . $hash;
        }
        return $this->_functionName;
    }

    /**
     * Returns prefix used for function name.
     * Function name is also base name for the template.
     *
     * @param int $timestamp unix timestamp with template modification date
     * @return string
     */
    private function getFunctionNamePrefix($timestamp)
    {
        // tpl_ prefix and last modified time must not be changed,
        // because cache cleanup relies on that
        return 'tpl_' . sprintf("%08x",$timestamp) .'_';
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
    final public function pushContext()
    {
        $this->_context = $this->_context->pushContext();
        return $this->_context;
    }

    /**
     * only for use in generated template code
     * @access private
     */
    final public function popContext()
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
        self::setIncludePath();
        require_once 'PHPTAL/Dom/DocumentBuilder.php';
        require_once 'PHPTAL/Php/CodeGenerator.php';
        self::restoreIncludePath();

        // instantiate the PHPTAL source parser
        $parser = new PHPTAL_Dom_SaxXmlParser($this->_encoding);
        $builder = new PHPTAL_Dom_DocumentBuilder();
        $builder->stripComments($this->_stripComments);

        $data = $this->_source->getData();
        $realpath = $this->_source->getRealPath();

        foreach($this->getPreFilters() as $prefilter) {
            if ($prefilter instanceof PHPTAL_PreFilter) {
                $prefilter->setPHPTAL($this);
            }
            $data = $prefilter->filter($data);
        }
        $tree = $parser->parseString($builder, $data, $realpath)->getResult();

        foreach($this->getPreFilters() as $prefilter) {
            if ($prefilter instanceof PHPTAL_PreFilter) {
                if ($prefilter->filterDOM($tree)) {
                    throw new PHPTAL_ConfigurationException("Don't return value from filterDOM()");
                }
            }
        }

        $generator = new PHPTAL_Php_CodeGenerator(
            $this->getFunctionName(),
            $this->_source->getRealPath(),
            $this->_encoding,
            $this->_outputMode,
            $this->getCodePath());
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

        if (!$this->_resolvers && !$this->_repositories)
        {
            $this->_source = new PHPTAL_FileSource($this->_path);
        }
        else
        {
            foreach ($this->_resolvers as $resolver) {
                $source = $resolver->resolve($this->_path);
                if ($source) {
                    $this->_source = $source;
                    return;
                }
            }

            $resolver = new PHPTAL_FileSourceResolver($this->_repositories);
            $this->_source = $resolver->resolve($this->_path);
        }

        if (!$this->_source) {
            throw new PHPTAL_IOException('Unable to locate template file '.$this->_path);
        }
    }

    /**
     * Set include path to contain PHPTAL's directory.
     * Every call to setIncludePath() MUST have corresponding call
     * to restoreIncludePath()!
     *
     * Calls to setIncludePath/restoreIncludePath can be nested.
     *
     * @return void
     */
    final public static function setIncludePath()
    {
        if (!self::$include_path_set_nesting) {
            self::$include_path_backup = get_include_path();
            set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());
        }
        self::$include_path_set_nesting++;
    }

    /**
     * Restore include path to state before PHPTAL modified it.
     */
    final public static function restoreIncludePath()
    {
        self::$include_path_set_nesting--;
        if (!self::$include_path_set_nesting) {
            // restore_include_path() doesn't work properly
            set_include_path(self::$include_path_backup);
        }
    }

    final public static function autoload($class)
    {
       static $except = array(
            'PHPTAL_FileSourceResolver'=>'PHPTAL_FileSource',
            'PHPTAL_NamespaceAttributeReplace'=>'PHPTAL_NamespaceAttribute',
            'PHPTAL_NamespaceAttributeSurround'=>'PHPTAL_NamespaceAttribute',
            'PHPTAL_NamespaceAttributeContent'=>'PHPTAL_NamespaceAttribute',
            'PHPTAL_TemplateException'=>'PHPTAL_Exception',
            'PHPTAL_IOException'=>'PHPTAL_Exception',
            'PHPTAL_ParserException'=>'PHPTAL_Exception',
            'PHPTAL_InvalidVariableNameException'=>'PHPTAL_Exception',
            'PHPTAL_VariableNotFoundException'=>'PHPTAL_Exception',
            'PHPTAL_ConfigurationException'=>'PHPTAL_Exception',
            'PHPTAL_UnknownModifierException'=>'PHPTAL_Exception',
            'PHPTAL_MacroMissingException'=>'PHPTAL_Exception',
        );

        if (!isset($except[$class])) {
            if (substr($class, 0, 7) !== 'PHPTAL_') return;
        }
        else {
            $class = $except[$class];
        }

        $path = dirname(__FILE__) . strtr("_".$class,"_",DIRECTORY_SEPARATOR) . '.php';

        require $path;
    }
}
