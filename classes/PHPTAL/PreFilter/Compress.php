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
        }

        // let xml:space=preserve preserve attributes as well
        if ($root->getAttributeNS("http://www.w3.org/XML/1998/namespace", 'space') == 'preserve') {
            $this->most_recent_text_node = null;
            $this->findElementToFilter($root);
            return;
        }

        $this->normalizeAttributes($root);

        // <pre> may have attributes normalized
        if ($this->isSpaceSensitiveInXHTML($root)) {
            $this->most_recent_text_node = null;
            $this->findElementToFilter($root);
            return;
        }

        if ($root->getAttributeNS('http://xml.zope.org/namespaces/tal','replace')) {
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

                if ($norm !== '') {
                    $this->most_recent_text_node = $node;
                    $this->had_space = (substr($norm,-1) == ' ');
                }

                $node->setValueEscaped($norm);

            } else if ($node instanceof PHPTAL_Dom_Element) {
                $this->filterDOM($node);
            } else if ($node instanceof PHPTAL_Dom_DocumentType || $node instanceof PHPTAL_Dom_XMLDeclaration) {
                $this->had_space = true;
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
        return in_array($element->getLocalName(), self::$no_interelement_space)
            && ($element->getNamespaceURI() === 'http://www.w3.org/1999/xhtml' || $element->getNamespaceURI() === '');
    }

    private static $breaks_line = array(
        'div','br','p','pre','tr','td','table','th','tbody','thead','ul','ol','form','title','head','html','body','base','link','meta','style',
        'fieldset','legend','hr','section','article','nav','aside','hgroup','header','footer','figure','h1','h2','h3','h4','h5','h6','address','blockquote','dd','dt','dl','param',
    );

    private function breaksLine(PHPTAL_Dom_Element $element)
    {
        return in_array($element->getLocalName(), self::$breaks_line)
            && ($element->getNamespaceURI() === 'http://www.w3.org/1999/xhtml' || $element->getNamespaceURI() === '');
    }
}
