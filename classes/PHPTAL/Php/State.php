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
require_once 'PHPTAL/Php/Tales.php';

/**
 * @package PHPTAL
 * @subpackage php
 */
class PHPTAL_Php_State
{
    private $_debug      = false;
    private $_talesMode  = 'tales';
    private $_encoding   = 'UTF-8';
    private $_outputMode = PHPTAL::XHTML;
    private $cache_basename = '/tmp/phptal';

    /**
     * used by codegenerator to pass information from PHPTAL class. Don't use otherwise.
     */
    public function setCacheFilesBaseName($name)
    {
        $this->cache_basename = $name;
    }

    /**
     * used by codewriter to get information for phptal:cache
     */
    public function getCacheFilesBaseName()
    {
        return $this->cache_basename;
    }

    /**
     * controlled by phptal:debug
     */
    public function setDebug($bool)
    {
        $old = $this->_debug;
        $this->_debug = $bool;
        return $old;
    }

    /**
     * if true, add additional diagnostic information to generated code
     */
    public function isDebugOn()
    {
        return $this->_debug;
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
        $old = $this->_talesMode;
        $this->_talesMode = $mode;
        return $old;
    }

    public function getTalesMode()
    {
        return $this->_talesMode;
    }

    /**
     * must be same as input's encoding and can't change.
     */
    public function setEncoding($enc)
    {
        $this->_encoding = $enc;
    }

    /**
     * encoding used for both template input and output
     */
    public function getEncoding()
    {
        return $this->_encoding;
    }

    /**
     * @param $mode one of PHPTAL::XHTML, PHPTAL::XML, PHPTAL::HTML5
     */
    public function setOutputMode($mode)
    {
        $this->_outputMode = $mode;
    }

    /**
     * Syntax rules to follow in generated code
     *
     * @return one of PHPTAL::XHTML, PHPTAL::XML, PHPTAL::HTML5
     */
    public function getOutputMode()
    {
        return $this->_outputMode;
    }

    /**
     * @return string with PHP code or array with expressions for TalesChainExecutor
     */
    public function evaluateExpression($expression)
    {
        if ($this->_talesMode == 'php')
            return PHPTAL_Php_TalesInternal::php($expression);
        return PHPTAL_Php_TalesInternal::compileToPHPStatements($expression,false);
    }

    /**
     * returns PHP code that generates given string, including dynamic replacements
     */
    public function interpolateTalesVarsInString($string)
    {
        if ($this->_talesMode == 'tales') {
            return PHPTAL_Php_TalesInternal::string($string);
        }

        // replace ${var} found in expression
        while (preg_match('/(?<!\$)\$\{([^\}]+)\}/s', $string, $m)){
            list($ori, $exp) = $m;
            $php  = PHPTAL_Php_TalesInternal::php($exp);
            $string = str_replace($ori, '\'.'.$php.'.\'', $string); // FIXME: that is not elegant
        }
        $string = str_replace('$${', '${', $string);
        return '\''.$string.'\'';
    }

    /**
     * callback for interpolation of TALES with structure keyword, i.e. without HTML-escapes
     */
    private function _interpolateTalesVarsStructure($matches)
    {
        if ($this->_talesMode == 'tales') $code = PHPTAL_Php_TalesInternal::compileToPHPExpression($matches[1]);
        else $code = PHPTAL_Php_TalesInternal::php($matches[1]);

        return '<?php echo '.$this->stringify($code).' ?>';
    }

    /**
     * callback for interpolating TALES with HTML-escaping
     */
    private function _interpolateTalesVarsHTML($matches)
    {
        if ($this->_talesMode == 'tales') {
            $code = PHPTAL_Php_TalesInternal::compileToPHPExpression(html_entity_decode($matches[1],ENT_QUOTES, $this->getEncoding()),false);
        } else $code = PHPTAL_Php_TalesInternal::php($matches[1]);

        return '<?php echo '.$this->htmlchars($code).' ?>';
    }

    /**
     * callback for interpolating TALES with CDATA escaping
     */
    private function _interpolateTalesVarsCDATA($matches)
    {
        if ($this->_talesMode == 'tales') {
            $code = PHPTAL_Php_TalesInternal::compileToPHPExpression($matches[1],ENT_QUOTES, $this->getEncoding());
        } else $code = PHPTAL_Php_TalesInternal::php($matches[1]);

        // quite complex for an "unescaped" section, isn't it?
        if ($this->getOutputMode() === PHPTAL::HTML5) {
            return "<?php echo str_replace('</','<\\\\/', phptal_tostring($code)) ?>";
        } elseif ($this->getOutputMode() === PHPTAL::XHTML) {
            // both XML and HMTL, because people will inevitably send it as text/html :(
            return "<?php echo strtr(phptal_tostring($code) ,array(']]>'=>']]]]><![CDATA[>','</'=>'<\\/')) ?>";
        } else {
            return "<?php echo str_replace(']]>',']]]]><![CDATA[>', phptal_tostring($code)) ?>";
        }
    }

    /**
     * replaces ${} in string, expecting HTML-encoded input and HTML-escapes output
     */
    public function interpolateTalesVarsInHtml($src)
    {
        // uses lookback assertion to exclude $${}
        $result = preg_replace_callback('/(?<!\$)\$\{structure (.*?)\}/is', array($this,'_interpolateTalesVarsStructure'), $src);
        $result = preg_replace_callback('/(?<!\$)\$\{(?:text )?(.*?)\}/is', array($this,'_interpolateTalesVarsHTML'), $result);
        $result = str_replace('$${', '${', $result);
        return $result;
    }

    /**
     * replaces ${} in string, expecting CDATA (basically unescaped) input,
     * generates output protected against breaking out of CDATA in XML/HTML
     * (depending on current output mode).
     */
    public function interpolateTalesVarsInCDATA($src)
    {
        $result = preg_replace_callback('/(?<!\$)\$\{structure (.*?)\}/is', array($this,'_interpolateTalesVarsStructure'), $src);
        $result = preg_replace_callback('/(?<!\$)\$\{(?:text )?(.*?)\}/is', array($this,'_interpolateTalesVarsCDATA'), $result);
        $result = str_replace('$${', '${', $result);
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
        return 'phptal_tostring('.$php.')';
    }
}

