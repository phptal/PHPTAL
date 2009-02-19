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
        $escape = true;
        if (preg_match('/^(text|structure)(?:\s+(.*)|\s*$)/',$this->expression,$m))
        {
            if ($m[1]=='structure') $escape=false;
            $this->expression = isset($m[2])?$m[2]:'';
        }
                
        // if no expression is given, the content of the node is used as 
        // a translation key
        if (strlen(trim($this->expression)) == 0){
            $key = $this->_getTranslationKey($this->tag, !$escape);
            $key = trim(preg_replace('/\s+/sm'.($this->tag->generator->getEncoding()=='UTF-8'?'u':''), ' ', $key));
            $code = '\'' . str_replace('\'', '\\\'', $key) . '\'';
        }
        else {
            $code = $this->tag->generator->evaluateExpression($this->expression);
        }
        $this->_prepareNames($this->tag);

        $php = sprintf('echo $_translator->translate(%s,%s);', $code, $escape ? 'true':'false');
        $this->tag->generator->pushCode($php);
    }

    public function end()
    {
    }

    private function _getTranslationKey($tag, $preserve_tags)
    {
        $result = '';
        foreach ($tag->children as $child){
            if ($child instanceOf PHPTAL_Php_Text){
				if ($preserve_tags)
				{
                $result .= $child->node->getValue();
            }
				else
				{
                	$result .= html_entity_decode($child->node->getValue(),ENT_QUOTES,$this->tag->generator->getEncoding());
				}
            }
            else if ($child instanceOf PHPTAL_Php_Element){
                if ($child->hasAttribute('i18n:name')){
                    $value = $child->getAttribute('i18n:name');
                    $result .= '${' . $value . '}';
                }
                else {
                    
                    if ($preserve_tags)
                    {
                        $result .= '<'.$child->name;
                        foreach($child->attributes as $k => $v)
                        {
                            $result .= ' '.$k.'="'.$v.'"';
                        }
                        $result .= '>'.$this->_getTranslationKey($child, $preserve_tags).'</'.$child->name.'>';
                    }
                    else
                    {                    
                        $result .= $this->_getTranslationKey($child, $preserve_tags);
                    }
                }
            }
        }
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
