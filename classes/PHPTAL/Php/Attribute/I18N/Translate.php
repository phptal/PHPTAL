<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Attribute.php';

// ZPTInternationalizationSupport
//
// i18n:translate
//
// This attribute is used to mark units of text for translation. If this 
// attribute is specified with an empty string as the value, the message ID 
// is computed from the content of the element bearing this attribute. 
// Otherwise, the value of the element gives the message ID.
// 

/**
 * @package phptal.php.attribute.i18n
 */
class PHPTAL_Php_Attribute_I18N_Translate extends PHPTAL_Php_Attribute
{
    public function start()
    {
        // if no expression is given, the content of the node is used as 
        // a translation key
        if (strlen(trim($this->expression)) == 0){
            $code = $this->_getTranslationKey($this->tag);
            $code = str_replace('\'', '\\\'', $code);
            $code = '\'' . $code . '\'';
        }
        else {
            $code = $this->tag->generator->evaluateExpression($this->expression);
        }
        $this->_prepareNames($this->tag);

        $php = sprintf('echo $_translator->translate(%s);', $code);
        $this->tag->generator->pushCode($php);
    }

    public function end()
    {
    }

    private function _getTranslationKey($tag)
    {
        $result = '';
        foreach ($tag->children as $child){
            if ($child instanceOf PHPTAL_Php_Text){
                $result .= $child->node->getValue();
            }
            else if ($child instanceOf PHPTAL_Php_Element){
                if ($child->hasAttribute('i18n:name')){
                    $value = $child->getAttribute('i18n:name');
                    $result .= '${' . $value . '}';
                }
                else {
                    $result .= $this->_getTranslationKey($child);
                }
            }
        }
        // cleanup result
        $result = preg_replace('/\s+/sm'.($this->tag->generator->getEncoding()=='UTF-8'?'u':''), ' ', $result);
        $result = trim($result);
        return $result;
    }

    private function _prepareNames($tag)
    {
        foreach ($tag->children as $child){
            if ($child instanceOf PHPTAL_Php_Element){
                if ($child->hasAttribute('i18n:name')){
                    $child->generate();
                }
                else {
                    $this->_prepareNames($child);
                }
            }
        }
    }
}

?>
