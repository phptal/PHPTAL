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

require_once 'PHPTAL/Tales.php';

/**
 * @package PHPTAL
 * @subpackage Php
 */
class PHPTAL_Php_State
{
    private $debug      = false;
    private $tales_mode = 'tales';
    private $encoding;
    private $output_mode;
    private $phptal;

    function __construct(PHPTAL $phptal)
    {
        $this->phptal = $phptal; 
        $this->encoding = $phptal->getEncoding();
        $this->output_mode = $phptal->getOutputMode();
    }

    /**
     * used by codewriter to get information for phptal:cache
     */
    public function getCacheFilesBaseName()
    {
        return $this->phptal->getCodePath();
    }

    /**
     * controlled by phptal:debug
     */
    public function setDebug($bool)
    {
        $old = $this->debug;
        $this->debug = $bool;
        return $old;
    }

    /**
     * if true, add additional diagnostic information to generated code
     */
    public function isDebugOn()
    {
        return $this->debug;
    }

    /**
     * Sets new and returns old TALES mode.
     * Valid modes are 'tales' and 'php'
     * 
     * @param string $mode
     * @return string
     */
    public function setTalesMode($mode)
    {
        $old = $this->tales_mode;
        $this->tales_mode = $mode;
        return $old;
    }

    public function getTalesMode()
    {
        return $this->tales_mode;
    }

    /**
     * encoding used for both template input and output
     */
    public function getEncoding()
    {
        return $this->encoding;
    }

    /**
     * Syntax rules to follow in generated code
     *
     * @return one of PHPTAL::XHTML, PHPTAL::XML, PHPTAL::HTML5
     */
    public function getOutputMode()
    {
        return $this->output_mode;
    }

    /**
     * Load prefilter
     */
    public function getPreFilterByName($name)
    {
        return $this->phptal->getPreFilterByName($name);
    }

    /**
     * compile TALES expression according to current talesMode
     * @return string with PHP code or array with expressions for TalesChainExecutor
     */
    public function evaluateExpression($expression)
    {
        if ($this->tales_mode === 'php') {
            return PHPTAL_Php_TalesInternal::php($expression);
        }
        return PHPTAL_Php_TalesInternal::compileToPHPStatements($expression,false);
    }

    /**
     * compile TALES expression according to current talesMode
     * @return string with PHP code 
     */
    private function compileTalesToPHPExpression($expression)
    {
        if ($this->tales_mode === 'php') {
            return PHPTAL_Php_TalesInternal::php($expression);
        }
        return PHPTAL_Php_TalesInternal::compileToPHPExpression($expression,false);
    }

    /**
     * returns PHP code that generates given string, including dynamic replacements
     *
     * It's almost unused.
     */
    public function interpolateTalesVarsInString($string)
    {
        if ($this->tales_mode == 'tales') {
            return PHPTAL_Php_TalesInternal::string($string);
        }

        // replace ${var} found in expression
        while (preg_match('/(?<!\$)\$\{([^\}]+)\}/s', $string, $m)){
            list($ori, $exp) = $m;
            $php  = PHPTAL_Php_TalesInternal::php($exp);
            $string = str_replace($ori, '\'.('.$php.').\'', $string); // FIXME: that is not elegant
        }
        $string = str_replace('$${', '${', $string); // FIXME: that is not elegant
        return '(\''.$string.'\')';
    }
    
    /**
     * helper function that changes HTML-escaped TALES expression to PHP code.
     * Generated PHP code does not apply HTML-escaping.
     */
    private function _interpolateTalesVars($src)
    {
        $src = html_entity_decode($src,ENT_QUOTES, $this->getEncoding());        
        return $this->compileTalesToPHPExpression($src);
    }

    /**
     * callback for interpolation of TALES with structure keyword, i.e. output without HTML-escapes, 
     * but input with HTML-escapes.
     */
    private function _interpolateTalesVarsHTMLStructure($matches)
    {
        return '<?php echo '.$this->stringify($this->_interpolateTalesVars($matches[1])).' ?>';
    }


    /**
     * callback for interpolation of TALES with structure keyword, i.e. input and output without HTML-escapes.
     */
    private function _interpolateTalesVarsCDATAStructure($matches)
    {        
        return '<?php echo '.$this->stringify($this->compileTalesToPHPExpression($matches[1])).' ?>';
    }

    /**
     * callback for interpolating TALES with HTML-escaping
     */
    private function _interpolateTalesVarsHTML($matches)
    {
        return '<?php echo '.$this->htmlchars($this->_interpolateTalesVars($matches[1])).' ?>';
    }

    /**
     * callback for interpolating TALES with CDATA escaping
     */
    private function _interpolateTalesVarsCDATA($matches)
    {
        $code = $this->compileTalesToPHPExpression($matches[1]);
        
        // quite complex for an "unescaped" section, isn't it?
        if ($this->getOutputMode() === PHPTAL::HTML5) {
            return "<?php echo str_replace('</','<\\\\/', ".$this->stringify($code).") ?>";
        } elseif ($this->getOutputMode() === PHPTAL::XHTML) {
            // both XML and HMTL, because people will inevitably send it as text/html :(
            return "<?php echo strtr(".$this->stringify($code)." ,array(']]>'=>']]]]><![CDATA[>','</'=>'<\\/')) ?>";
        } else {
            return "<?php echo str_replace(']]>',']]]]><![CDATA[>', ".$this->stringify($code).") ?>";
        }
    }

    /**
     * replaces ${} in string, expecting HTML-encoded input and HTML-escapes output
     */
    public function interpolateTalesVarsInHtml($src)
    {
        // uses lookback assertion to exclude $${}
        $result = preg_replace_callback('/(?<!\$)\$\{structure (.*?)\}/is', array($this,'_interpolateTalesVarsHTMLStructure'), $src);
        $result = preg_replace_callback('/(?<!\$)\$\{(?:text )?(.*?)\}/is', array($this,'_interpolateTalesVarsHTML'), $result);
        $result = str_replace('$${', '${', $result); // FIXME: could change it inside compiled code, which breaks things
        return $result;
    }

    /**
     * replaces ${} in string, expecting CDATA (basically unescaped) input,
     * generates output protected against breaking out of CDATA in XML/HTML
     * (depending on current output mode).
     */
    public function interpolateTalesVarsInCDATA($src)
    {
        $result = preg_replace_callback('/(?<!\$)\$\{structure (.*?)\}/is', array($this,'_interpolateTalesVarsCDATAStructure'), $src);
        $result = preg_replace_callback('/(?<!\$)\$\{(?:text )?(.*?)\}/is', array($this,'_interpolateTalesVarsCDATA'), $result);
        $result = str_replace('$${', '${', $result); // FIXME: could change it inside compiled code, which breaks things
        return $result;
    }

    /**
     * expects PHP code and returns PHP code that will generate escaped string
     * Optimizes case when PHP string is given.
     *
     * @return php code
     */
    public function htmlchars($php)
    {
        // PHP strings can be escaped at compile time
        if (preg_match('/^\'((?:[^\'{]+|\\\\.)*)\'$/', $php, $m))
        {
            return "'".htmlspecialchars(str_replace('\\\'',"'", $m[1]), ENT_QUOTES)."'";
        }
        return 'phptal_escape('.$php.')';
    }

    /**
     * allow proper printing of any object
     * (without escaping - for use with structure keyword)
     *
     * @return php code
     */
    public function stringify($php)
    {
        // PHP strings don't need to be changed
        if (preg_match('/^\'(?:[^\'{]+|\\\\.)*\'$/', $php))
        {
            return $php;
        }
        return 'phptal_tostring('.$php.')';
    }
}

