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
 *  i18n:attributes
 *
 * This attribute will allow us to translate attributes of HTML tags, such 
 * as the alt attribute in the img tag. The i18n:attributes attribute 
 * specifies a list of attributes to be translated with optional message 
 * IDs? for each; if multiple attribute names are given, they must be 
 * separated by semi-colons. Message IDs? used in this context must not 
 * include whitespace.
 *
 * Note that the value of the particular attributes come either from the 
 * HTML attribute value itself or from the data inserted by tal:attributes.
 *
 * If an attibute is to be both computed using tal:attributes and translated, 
 * the translation service is passed the result of the TALES expression for 
 * that attribute.
 *
 * An example:
 *
 *     <img src="http://foo.com/logo" alt="Visit us"
 *              tal:attributes="alt here/greeting"
 *              i18n:attributes="alt"
 *              />
 *
 *
 * In this example, let tal:attributes set the value of the alt attribute to 
 * the text "Stop by for a visit!". This text will be passed to the 
 * translation service, which uses the result of language negotiation to 
 * translate "Stop by for a visit!" into the requested language. The example 
 * text in the template, "Visit us", will simply be discarded.
 *
 * Another example, with explicit message IDs:
 *
 *   <img src="../icons/uparrow.png" alt="Up"
 *        i18n:attributes="src up-arrow-icon; alt up-arrow-alttext"
 *   >
 *
 * Here, the message ID up-arrow-icon will be used to generate the link to 
 * an icon image file, and the message ID up-arrow-alttext will be used for 
 * the "alt" text.
 *
 *
 *
 * @package PHPTAL.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Attributes extends PHPTAL_Php_Attribute
{
    public function start(PHPTAL_Php_CodeWriter $codewriter)
    {
        // split attributes to translate
        foreach ($codewriter->splitExpression($this->expression) as $exp) {            
            list($qname, $key) = $this->parseSetExpression($exp);
                        
            if (strlen($key)) // if the translation key is specified and not empty (but may be '0')
            {
                // we use it and replace the tag attribute with the result of the translation
                $code = $this->_getTranslationCode($codewriter, $key);
            } else {                
                $attr = $this->phpelement->getAttributeNode($qname);
                if (!$attr) throw new PHPTAL_TemplateException("Unable to translate attribute $qname, because there is no translation key specified");

                if ($attr->getReplacedState() === PHPTAL_DOMAttr::NOT_REPLACED) {
                    $code = $this->_getTranslationCode($codewriter, $attr->getValue());
                } elseif ($attr->getReplacedState() === PHPTAL_DOMAttr::VALUE_REPLACED && $attr->getOverwrittenVariableName()) {
                        // sadly variables won't be interpolated in this translation
                        $code = 'echo '.$codewriter->escapeCode('$_translator->translate('.$attr->getOverwrittenVariableName().', false)');
                } else {
                        throw new PHPTAL_TemplateException("Unable to translate attribute $qname, because other TAL attributes are using it");
                }
            }
            $this->phpelement->getOrCreateAttributeNode($qname)->overwriteValueWithCode($code);
        }
    }
   
    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
    }

    /**
     * @param key - unescaped string (not PHP code) for the key
     */
    private function _getTranslationCode(PHPTAL_Php_CodeWriter $codewriter, $key)
    {
		$code = '';
    	if (preg_match_all('/\$\{(.*?)\}/', $key, $m)){
			array_shift($m);
			$m = array_shift($m);
			foreach ($m as $name) {
				$code .= "\n".'$_translator->setVar('.$codewriter->str($name).','.phptal_tale($name).');'; // allow more complex TAL expressions
			}
			$code .= "\n";
		}

        // notice the false boolean which indicate that the html is escaped
        // elsewhere looks like an hack doesn't it ? :)
        $code .= 'echo '.$codewriter->escapeCode('$_translator->translate('.$codewriter->str($key).', false)');
		return $code;
    }
}


