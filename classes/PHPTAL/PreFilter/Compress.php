<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: $
 * @link     http://phptal.org/
 */

/**
 * Removes all unnecessary whitespace from XHTML documents.
 *
 * extends Normalize only to re-use helper methods
 */
class PHPTAL_PreFilter_Compress extends PHPTAL_PreFilter_Normalize
{
    private $had_space=false, $most_recent_text_node=null;

    function filterDOM(PHPTAL_Dom_Element $root)
    {
        $no_spaces = $this->hasNoInterelementSpace($root);
        $breaks_line = $no_spaces || $this->breaksLine($root);

        if ($breaks_line) {
            $this->had_space = true;
        } else if ($this->isInlineBlock($root)) {
            $this->most_recent_text_node = null;
            $this->had_space = false;
        }

        // let xml:space=preserve preserve attributes as well
        if ($root->getAttributeNS("http://www.w3.org/XML/1998/namespace", 'space') == 'preserve') {
            $this->most_recent_text_node = null;
            $this->findElementToFilter($root);
            return;
        }

        $this->normalizeAttributes($root);
        $this->elementSpecificOptimizations($root);

        // <pre> may have attributes normalized
        if ($this->isSpaceSensitiveInXHTML($root)) {
            $this->most_recent_text_node = null;

            // HTML 5 (9.1.2.5) specifies quirk that a first *single* newline in <pre> can be removed
            if (count($root->childNodes) && $root->childNodes[0] instanceof PHPTAL_Dom_Text) {
                if (substr($root->childNodes[0]->getValueEscaped(),0,1)==="\n") {
                    $root->childNodes[0]->setValueEscaped(substr($root->childNodes[0]->getValueEscaped(),1));
                }
            }
            $this->findElementToFilter($root);
            return;
        }

        if ($root->getAttributeNS('http://xml.zope.org/namespaces/tal','replace')) {
            $this->most_recent_text_node = null;
            $this->had_space = false;
            return;
        }

        foreach ($root->childNodes as $node) {

            if ($node instanceof PHPTAL_Dom_Text) {
                $norm = $this->normalizeSpace($node->getValueEscaped(), $node->getEncoding());

                if ($no_spaces) {
                    $norm = trim($norm);
                } elseif ($this->had_space) {
                    $norm = ltrim($norm);
                }

                $node->setValueEscaped($norm);

                if ($norm !== '') {
                    $this->most_recent_text_node = $node;
                    $this->had_space = (substr($norm,-1) == ' ');
                }
            } else if ($node instanceof PHPTAL_Dom_Element) {
                $this->filterDOM($node);
            } else if ($node instanceof PHPTAL_Dom_DocumentType || $node instanceof PHPTAL_Dom_XMLDeclaration) {
                $this->had_space = true;
            } else if ($node instanceof PHPTAL_Dom_ProcessingInstruction) {
                $this->most_recent_text_node = null;
                $this->had_space = false;
            }
        }

        if ($breaks_line) {
            if ($this->most_recent_text_node) {
                $this->most_recent_text_node->setValueEscaped(rtrim($this->most_recent_text_node->getValueEscaped()));
            }
            $this->had_space = true;
        }
    }

    private static $no_interelement_space = array(
        'html','head','table','thead','tfoot','select','optgroup','dl','ol','ul','tr','datalist',
    );

    private function hasNoInterelementSpace(PHPTAL_Dom_Element $element)
    {
        if ($element->getLocalName() === 'block'
            && $element->parentNode
            && $element->getNamespaceURI() === 'http://xml.zope.org/namespaces/tal') {
            return $this->hasNoInterelementSpace($element->parentNode);
        }

        return in_array($element->getLocalName(), self::$no_interelement_space)
            && ($element->getNamespaceURI() === 'http://www.w3.org/1999/xhtml' || $element->getNamespaceURI() === '');
    }

    private static $breaks_line = array(
        'address','article','aside','base','blockquote','body','br','dd','div','dl','dt','fieldset','figure',
        'footer','form','h1','h2','h3','h4','h5','h6','head','header','hgroup','hr','html','legend','link',
        'meta','nav','ol','option','p','param','pre','section','style','table','tbody','td','th','thead',
        'title','tr','ul','details',
    );

    private function breaksLine(PHPTAL_Dom_Element $element)
    {
        if ($element->getAttributeNS('http://xml.zope.org/namespaces/metal','define-macro')) {
            return true;
        }

        if (!$element->parentNode) {
            return true;
        }

        if ($element->getLocalName() === 'block'
            && $element->getNamespaceURI() === 'http://xml.zope.org/namespaces/tal') {
            return $this->breaksLine($element->parentNode);
        }

        if ($element->getNamespaceURI() !== 'http://www.w3.org/1999/xhtml'
	        && $element->getNamespaceURI() !== '') {
	        return false;
        }

        return in_array($element->getLocalName(), self::$breaks_line);
    }
    
    private static $inline_blocks = array(
        'select','input','button','img','textarea','output','progress','meter',
    );

    private function isInlineBlock(PHPTAL_Dom_Element $element)
    {        
        if ($element->getNamespaceURI() !== 'http://www.w3.org/1999/xhtml'
	        && $element->getNamespaceURI() !== '') {
	        return false;
        }

        return in_array($element->getLocalName(), self::$inline_blocks);
    }

    /**
     * Consistent sorting of attributes might give slightly better gzip performance
     */
    protected function normalizeAttributes(PHPTAL_Dom_Element $element)
    {
        parent::normalizeAttributes($element);

        $attrs_by_qname = array();
        foreach ($element->getAttributeNodes() as $attrnode) {
            // safe, as there can't be two attrs with same qname
            $attrs_by_qname[$attrnode->getQualifiedName()] = $attrnode;
        }

    	if (count($attrs_by_qname) > 1) {
    		uksort($attrs_by_qname, array($this, 'compareQNames'));
    		$element->setAttributeNodes(array_values($attrs_by_qname));
    	}
    }

    /**
	 * pre-defined order of attributes roughly by popularity
	 */
	private static $attributes_order = array(
        'href','src','class','rel','type','title','width','height','alt','content','name','style','lang','id',
    );

	/**
	 * compare names according to $attributes_order array.
	 * Elements that are not in array, are considered greater than all elements in array,
	 * and are sorted alphabetically.
	 */
	private static function compareQNames($a, $b) {
		$a_index = array_search($a, self::$attributes_order);
		$b_index = array_search($b, self::$attributes_order);

		if ($a_index !== false && $b_index !== false) {
			return $a_index - $b_index;
		}
		if ($a_index === false && $b_index === false) {
			return strcmp($a, $b);
		}
		return ($a_index === false) ? 1 : -1;
	}

	private function elementSpecificOptimizations(PHPTAL_Dom_Element $element)
	{
	    if ($element->getNamespaceURI() !== 'http://www.w3.org/1999/xhtml'
	     && $element->getNamespaceURI() !== '') {
	        return;
        }

        if ($this->getPHPTAL()->getOutputMode() !== PHPTAL::HTML5) {
            return;
        }

	    if ('meta' === $element->getLocalName() &&
	        $element->getAttributeNS('','http-equiv') === 'Content-Type') {
	            $element->removeAttributeNS('','http-equiv');
	            $element->removeAttributeNS('','content');
	            $element->setAttributeNS('','charset',strtolower($this->getPHPTAL()->getEncoding()));
        }
        elseif ('link' === $element->getLocalName() &&
            $element->getAttributeNS('','rel') === 'stylesheet') {
            $element->removeAttributeNS('','type');

        } elseif ('script' === $element->getLocalName()) {
            $element->removeAttributeNS('','language');

            $type = $element->getAttributeNS('','type');
            $is_std = preg_match('/^(?:text|application)\/(?:ecma|java)script(\s*;\s*charset\s*=\s*[^;]*)?$/', $type);
            // For remote scripts type attr shouldn't matter
            if ($is_std || $element->getAttributeNS('','src')) {
                $element->removeAttributeNS('','type');
            }
        }
        elseif ('style' === $element->getLocalName()) {
            $element->removeAttributeNS('','type');
        }
    }
}
