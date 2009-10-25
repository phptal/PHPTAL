<?php

class PHPTAL_PreFilter_Normalize extends PHPTAL_PreFilter
{
    function filter($src)
    {
        return str_replace("\r\n","\n",$src);
    }
    
    function filterDOM(PHPTAL_Dom_Element $root)
    {
        // let xml:space=preserve preserve attributes as well
        if ($root->getAttributeNS("http://www.w3.org/XML/1998/namespace",'space') == 'preserve') {
            $this->findElementToFilter($root);
            return;
        }
        
        $this->normalizeAttributes($root);   

        // <pre> may have attributes normalized
        if ($this->isSpaceSensitiveInXHTML($root)) {
            $this->findElementToFilter($root);
            return;
        }            
        
        $lastTextNode = NULL;
        foreach($root->childNodes as $node) {
            
            // CDATA is not normalized by design
            if ($node instanceOf PHPTAL_Dom_Text) {                
                $norm = $this->normalizeSpace($node->getValueEscaped(), $node->getEncoding());
                $node->setValueEscaped($norm);
                
                if ('' === $norm) {
                    $root->removeChild($node);
                } else if ($lastTextNode) {                                        
                    // "foo " . " bar" gives 2 spaces.
                    $norm = str_replace('  ',' ',$lastTextNode->getValueEscaped().$norm);
                    
                    $lastTextNode->setValueEscaped($norm); // assumes all nodes use same encoding (they do)
                    $root->removeChild($node);
                } else {
                    $lastTextNode = $node;
                }
            } else {
                $lastTextNode = NULL;
                if ($node instanceOf PHPTAL_Dom_Element) {                                             
                    $this->filterDOM($node);
                }
            }
        }    
    }
    
    /**
     * Allows <script> to be normalize. Love your semicolons! (or use CDATA)
     */
    private function isSpaceSensitiveInXHTML(PHPTAL_Dom_Element $element)
    {
        return ($element->getLocalName() === 'pre' || $element->getLocalName() === 'textarea')  
            && ($element->getNamespaceURI() === 'http://www.w3.org/1999/xhtml' || $element->getNamespaceURI() === '');
    }
    
    private function findElementToFilter(PHPTAL_Dom_Element $root)
    {
        foreach($root->childNodes as $node)
        {
            if (!$node instanceOf PHPTAL_Dom_Element) continue;
            
            if ($node->getAttributeNS("http://www.w3.org/XML/1998/namespace",'space') == 'default') {
                $this->filterDOM($node);
            }
        }
    }
    
    /**
     * does not trim
     */
    private function normalizeSpace($text, $encoding)
    {
        $utf_regex_mod = ($encoding=='UTF-8'?'u':'');
        
        return preg_replace('/\s+/'.$utf_regex_mod,' ',$text);
    }
    
    function normalizeAttributes(PHPTAL_Dom_Element $element)
    {
        foreach($element->getAttributeNodes() as $attrnode) {
            if ($attrnode->getReplacedState() !== PHPTAL_Dom_Attr::NOT_REPLACED) continue;
            
            $val = $this->normalizeSpace($attrnode->getValueEscaped(), $attrnode->getEncoding());
            $attrnode->setValueEscaped(trim($val,' '));
        }
    }
}