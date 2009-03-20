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
    public function start(PHPTAL_Php_CodeWriter $codewriter)
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
            $key = $this->_getTranslationKey($this->phpelement, !$escape, $codewriter->getEncoding());
            $key = trim(preg_replace('/\s+/sm'.($codewriter->getEncoding()=='UTF-8'?'u':''), ' ', $key));
            $code = '\'' . str_replace('\'', '\\\'', $key) . '\'';
        }
        else {
            $code = $codewriter->evaluateExpression($this->expression);
        }
        $this->_prepareNames($codewriter, $this->phpelement);

        $php = sprintf('echo $_translator->translate(%s,%s);', $code, $escape ? 'true':'false');
        $codewriter->pushCode($php);
    }

    public function end(PHPTAL_Php_CodeWriter $codewriter)
    {
    }

    private function _getTranslationKey(PHPTAL_Php_Node $tag, $preserve_tags, $encoding)
    {
        $result = '';
        foreach ($tag->childNodes as $child){
            if ($child instanceOf PHPTAL_Php_Text){
				if ($preserve_tags)
				{
                $result .= $child->node->getValue();
            }
				else
				{
                	$result .= html_entity_decode($child->node->getValue(),ENT_QUOTES,$encoding);
				}
            }
            else if ($child instanceOf PHPTAL_Php_Element){
                if ($child->hasAttribute('i18n:name')){
                    $value = $child->getAttributeText('i18n:name', $encoding);
                    $result .= '${' . $value . '}';
                }
                else {
                    
                    if ($preserve_tags)
                    {
                        $result .= '<'.$child->getQualifiedName();
                        foreach($child->getEscapedAttributeValuesByQualifiedName() as $k => $v)
                        {
                            $result .= ' '.$k.'="'.$v.'"';
                        }
                        $result .= '>'.$this->_getTranslationKey($child, $preserve_tags,$encoding) . '</'.$child->getQualifiedName().'>';
                    }
                    else
                    {                    
                        $result .= $this->_getTranslationKey($child, $preserve_tags, $encoding);
                    }
                }
            }
        }
        return $result;
    }

    private function _prepareNames(PHPTAL_Php_CodeWriter $codewriter, PHPTAL_Php_Node $tag)
    {
        foreach ($tag->childNodes as $child){
            if ($child instanceOf PHPTAL_Php_Element){
                if ($child->hasAttribute('i18n:name')){
                    $child->generate($codewriter);
                }
                else {
                    $this->_prepareNames($codewriter,$child);
                }
            }
        }
    }
}

?>
