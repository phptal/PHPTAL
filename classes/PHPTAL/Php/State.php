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
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

/** 
 * @package PHPTAL.php
 */
class PHPTAL_Php_State
{
    private $_debug      = false;
    private $_talesMode  = 'tales';
    private $_encoding   = 'UTF-8';
    private $_outputMode = PHPTAL::XHTML;
    private $cache_basename = '/tmp/phptal';

    public function setCacheFilesBaseName($name)
    {
        $this->cache_basename = $name;
    }
    
    public function getCacheFilesBaseName()
    {
        return $this->cache_basename;
    }

    public function setDebug($bool)
    {
        $old = $this->_debug;
        $this->_debug = $bool;
        return $old;
    }

    public function isDebugOn()
    {
        return $this->_debug;
    }

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

    public function setEncoding($enc)
    {
        $this->_encoding = $enc;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    public function setOutputMode($mode)
    {
        $this->_outputMode = $mode;
    }

    public function getOutputMode()
    {
        return $this->_outputMode;
    }

    public function evalTalesExpression($expression)
    {
        if ($this->_talesMode == 'php')
            return PHPTAL_TalesInternal::php($expression);
        return phptal_tales($expression);
    }

    public function interpolateTalesVarsInString($string)
    {
        if ($this->_talesMode == 'tales') {
            return PHPTAL_TalesInternal::string($string);
        }
        
        // replace ${var} found in expression
        while (preg_match('/(?<!\$)\$\{([^\}]+)\}/s', $string, $m)){
            list($ori, $exp) = $m;
            $php  = PHPTAL_TalesInternal::php($exp);
            $string = str_replace($ori, '\'.'.$php.'.\'', $string); // FIXME: that is not elegant
        }
	    $string = str_replace('$${', '${', $string);
        return '\''.$string.'\'';
    }

    private function _interpolateTalesVarsStructure($matches) 
    {        
        if ($this->_talesMode == 'tales') $code = phptal_tale($matches[1]);      
        else $code = PHPTAL_TalesInternal::php($matches[1]);

        return '<?php echo '.$code.' ?>';
    }

    private function _interpolateTalesVarsHTML($matches) 
    {
        if ($this->_talesMode == 'tales') {
            $code = phptal_tale(html_entity_decode($matches[1],ENT_QUOTES, $this->getEncoding()));
        } else $code = PHPTAL_TalesInternal::php($matches[1]);     
        
        return '<?php echo '.$this->htmlchars($code).' ?>';
    }

    private function _interpolateTalesVarsCDATA($matches) 
    {
        if ($this->_talesMode == 'tales') {
            $code = phptal_tale($matches[1],ENT_QUOTES, $this->getEncoding());
        } else $code = PHPTAL_TalesInternal::php($matches[1]);     
        
        // quite complex for an "unescaped" section, isn't it?
        if ($this->getOutputMode() === PHPTAL::HTML5) {
            return "<?php echo str_replace('</','<\\\\/', $code) ?>";
        } elseif ($this->getOutputMode() === PHPTAL::XHTML) {
            // both XML and HMTL, because people will inevitably send it as text/html :(
            return "<?php echo strtr($code ,array(']]>'=>']]]]><![CDATA[>','</'=>'<\\/')) ?>";
        } else {
            return "<?php echo str_replace(']]>',']]]]><![CDATA[>', $code) ?>";
        }
    }

    public function interpolateTalesVarsInHtml($src)
    {
        $result = preg_replace_callback('/(?<!\$)\$\{structure (.*?)\}/is', array($this,'_interpolateTalesVarsStructure'), $src);
        $result = preg_replace_callback('/(?<!\$)\$\{(?:text )?(.*?)\}/is', array($this,'_interpolateTalesVarsHTML'), $result);
		$result = str_replace('$${', '${', $result);
		return $result;       
    }

    public function interpolateTalesVarsInCDATA($src)
    {
        $result = preg_replace_callback('/(?<!\$)\$\{structure (.*?)\}/is', array($this,'_interpolateTalesVarsStructure'), $src);
        $result = preg_replace_callback('/(?<!\$)\$\{(?:text )?(.*?)\}/is', array($this,'_interpolateTalesVarsCDATA'), $result);
		$result = str_replace('$${', '${', $result);
		return $result;       
    }

    public function htmlchars($php)
    {
        // PHP strings can be escaped at compile time
        if (preg_match('/^\'((?:[^\'{]+|\\\\.)*)\'$/', $php, $m))
        {
            return "'".htmlspecialchars(str_replace('\\\'',"'", $m[1]), ENT_QUOTES)."'";
        }        
        return 'phptal_escape('.$php.')';
    }
}

