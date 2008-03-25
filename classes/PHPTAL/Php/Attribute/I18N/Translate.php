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

        $php = sprintf('echo $tpl->getTranslator()->translate(%s);', self::_canonalizeKey($code));
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
        $result = preg_replace('/\s+/sm', ' ', $result);
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

    static function _canonalizeKey($key_)
    {
        $result = "";
        $key_ = trim($key_);
        $key_ = str_replace("\n", "", $key_);
        $key_ = str_replace("\r", "", $key_);
        for ($i = 0; $i<strlen($key_); $i++){
            $c = $key_[$i];
            $o = ord($c);
            if ($o < 5 || $o > 127){
                $result .= 'C<'.$o.'>';
            }
            else {
                $result .= $c;
            }
        }
        return $result;
        /*
        $key = utf8_decode($key_);
        $key = preg_replace('/[ав]/sm', 'a', $key);
        $key = preg_replace('/[клий]/sm', 'e', $key);
        $key = preg_replace('/[оп]/sm', 'i', $key);
        $key = preg_replace('/[ыь]/sm', 'u', $key);
        $key = preg_replace('/[фц]/sm', 'o', $key);
        $key = preg_replace('/[я]/sm', 'y', $key);
        return $key;
        */
    }
}

?>
