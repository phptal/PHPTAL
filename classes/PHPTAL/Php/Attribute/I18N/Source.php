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
 * @version  SVN: $Id: PHPTAL.php 517 2009-04-07 10:56:30Z kornel $
 * @link     http://phptal.motion-twin.com/ 
 */
 
 
/**
 * i18n:source
 * 
 *  The i18n:source attribute specifies the language of the text to be 
 *  translated. The default is "nothing", which means we don't provide 
 *  this information to the translation services.
 *  
 *
 * @package PHPTAL.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Source extends PHPTAL_Php_Attribute
{
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        // ensure that a sources stack exists or create it
        $codewriter->doIf('!isset($__i18n_sources)');
        $codewriter->pushCode('$__i18n_sources = array()');
        $codewriter->end();

        // push current source and use new one
        $codewriter->pushCode('$__i18n_sources[] = $_translator->setSource('.$codewriter->str($this->expression).')');
    }

    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
        // restore source
        $code = '$_translator->setSource(array_pop($__i18n_sources))';
        $codewriter->pushCode($code);
    }
}

