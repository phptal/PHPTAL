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
/**
 * @package PHPTAL
 */
class PHPTAL_Exception extends Exception
{
}

class PHPTAL_TemplateException extends PHPTAL_Exception
{
    public $srcFile;
    public $srcLine;

    public function __construct($msg, $srcFile=false, $srcLine=false)
    {
        parent::__construct($msg);
        $this->srcFile = $srcFile;
        $this->srcLine = $srcLine;
                
        if ($srcFile) {
            $this->file = $srcFile;
            $this->line = $srcLine;
        }
    }

    public function __toString()
    {
        if (!$this->srcFile) return parent::__toString();
        return "From {$this->srcFile} around line {$this->srcLine}\n".parent::__toString();
    }
    
    /**
     * set new source line/file only if one hasn't been set previously
     */
    public function hintSrcPosition($srcFile, $srcLine)
    {
        if ($srcFile && !$this->srcFile) {
            $this->srcFile = $srcFile; $this->srcLine = $srcLine;
        } elseif ($srcLine && $this->srcFile === $srcFile && !$this->srcLine) {
            $this->srcLine = $srcLine;
        }
        
        $this->file = $this->srcFile;
        $this->line = $this->srcLine;
    }
}

class PHPTAL_IOException extends PHPTAL_Exception {}
class PHPTAL_InvalidVariableNameException extends PHPTAL_Exception {}
class PHPTAL_ConfigurationException extends PHPTAL_Exception {}
class PHPTAL_VariableNotFoundException extends PHPTAL_TemplateException {}
class PHPTAL_ParserException extends PHPTAL_TemplateException {}
class PHPTAL_MacroMissingException extends PHPTAL_TemplateException {}

