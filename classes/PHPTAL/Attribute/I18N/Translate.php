<?php

// ZPTInternationalizationSupport
//
// i18n:translate
//
// This attribute is used to mark units of text for translation. If this 
// attribute is specified with an empty string as the value, the message ID 
// is computed from the content of the element bearing this attribute. 
// Otherwise, the value of the element gives the message ID.
// 
class PHPTAL_Attribute_I18N_Translate extends PHPTAL_Attribute
{
    function start()
    {
        // if no expression is given, the content of the node is used as 
        // a translation key
        if (strlen(trim($this->expression)) == 0){
            $code = $this->_translateContent($this->tag);
            $code = str_replace('\'', '\\\'', $code);
            $code = '\'' . $code . '\'';
        }
        else {
            $code = $this->tag->generator->evaluateExpression($this->expression);
            // sub nodes may contains i18n:name attributes
            $this->_translateContent($this->tag);
        }
        $code = sprintf('echo $tpl->getTranslator()->translate(%s)', $code);
        $this->tag->generator->pushCode($code);
        // $this->tag->generator->doEcho($key, false);
    }

    function end()
    {
    }

    function _translateContent($tag)
    {
        $result = "";
        foreach ($tag->children as $child){
            if ($child instanceOf PHPTAL_NodeText){
                $result .= $child->value;
            }
            else if (array_key_exists('i18n:name', $child->attributes)){
                $value = $child->attributes['i18n:name'];
                $result .= '${' . $value . '}';
                $child->generate();
            }
            else {
                $result .= $this->_translateContent($child);
            }
        }
        $result = preg_replace('/\s+/sm', ' ', $result);
        $result = trim($result);
        return $result;
    }
}

?>
