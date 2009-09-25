<?php

class PHPTAL_StripCommentsPreFilter extends PHPTAL_PreFilter 
{
    function filterDOM(PHPTAL_Dom_Element $element)
    {
        foreach($element->childNodes as $node) {
            if ($node instanceof PHPTAL_Dom_Comment) {
                $node->parentNode->removeChild($node);
            }
            else if ($node instanceof PHPTAL_Dom_Element) {
                $this->filterDOM($node);
            }
        }
    }
}
