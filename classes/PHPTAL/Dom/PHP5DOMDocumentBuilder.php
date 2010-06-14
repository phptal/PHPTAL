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
 * DOM Builder
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_PHP5DOMDocumentBuilder extends PHPTAL_Dom_DocumentBuilder
{
    private $document, $root;
    private $xmldecl = '', $doctype = '';

    public function getResult()
    {
        return $this->document->documentElement;
    }

    /**
     * XMLDecl can't be stored in DOM exactly
     *
     * @return string
     */
    public function getXMLDeclaration()
    {
        return $this->xmldecl;
    }


    // ~~~~~ XmlParser implementation ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public function onDocumentStart()
    {
        $this->setDocument(new DOMDocument('1.0','UTF-8'));
    }

    private function setDocument(DOMDocument $document)
    {
        $this->document = $document;

        $this->root = $this->document->createDocumentFragment();
        $this->_current = $this->root;
    }

    public function onDocumentEnd()
    {
        if ($this->root->childNodes->length == 1 && $this->root->childNodes->item(0) instanceof DOMElement) {
            $this->document->appendChild($this->root);
        } else {
            $wrapper = $this->document->createElementNS('http://xml.zope.org/namespaces/tal','tal:documentElement');
            if ($this->root->childNodes->length) {
                $wrapper->appendChild($this->root);
            }
            $this->document->appendChild($wrapper);
        }

        if (count($this->_stack) > 0) {
            $left='</'.$this->getQName($this->_current).'>';
            for ($i = count($this->_stack)-1; $i>0; $i--) $left .= '</'.$this->getQName($this->_stack[$i]).'>';
            throw new PHPTAL_ParserException("Not all elements were closed before end of the document. Missing: ".$left);
        }
    }

    public function onDocType($doctype_string)
    {
        $this->doctype = $doctype_string;

        // This is insane. PHP has no other way of setting DOCTYPE.
        // I'm not sure if preserving XMLDecl is a good idea.
        $src = $this->xmldecl . $doctype_string . '<tal:temp xmlns:tal="http://xml.zope.org/namespaces/tal"/>';

        $document = new DOMDocument('1.0','UTF-8');
        $document->loadXML($src);
        $document->removeChild($document->documentElement);
        $this->setDocument($document);
    }

    public function onXmlDecl($decl)
    {
        $this->xmldecl = $decl;
        if (preg_match('/\s+standalone\s*=\s*["\']yes/',$decl)) {
            $this->document->xmlStandalone = true;  // I'm not sure if that's needed at all
        }
    }

    public function onComment($data)
    {
        $this->_current->appendChild($this->document->createComment($data));
    }

    public function onCDATASection($data)
    {
        $this->_current->appendChild($this->document->createCDATASection($data));
    }

    public function onProcessingInstruction($data)
    {
        // FIXME: doesn't allow non-ASCII target names
        if (preg_match('/^<\?([A-Z0-9_:.-]+)\s+(.*)\?>$/si', $data, $m)) {
            list(,$target, $data) = $m;
            $this->_current->appendChild($this->document->createProcessingInstruction($target, $data));
        } else {
            throw new PHPTAL_ParserException("Invalid processsing instruction syntax (PI must start with name followed by whitespace, XML forbids PHP short tags) '$data'");
        }
    }

    private function decodeEntities($str)
    {
        return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    private function prefixToNamespaceURI($prefix, array $attributes)
    {
        if ($prefix === '') {
            if (isset($attributes['xmlns'])) {
                return trim($this->decodeEntities($attributes['xmlns']));
            }
            return $this->_current->namespaceURI; // not used for attributes!
        }

        // FIXME: this should be covered by lookupNamespaceURI() if xmlns attrs were processed first
        if (isset($attributes['xmlns:'.$prefix])) {
            return trim($this->decodeEntities($attributes['xmlns:'.$prefix]));
        }

        if ($res = $this->_current->lookupNamespaceURI($prefix)) {
            return $res;
        }

        $res = PHPTAL_Dom_Defs::getInstance()->prefixToNamespaceURI($prefix);
        return $res;
    }

    private function findParentWithoutPrefix(DOMNode $current)
    {
        while($current instanceof DOMElement && ($current = $current->parentNode)) {
            if (!$current->prefix) {
                return $current;
            }
        }
        return NULL;
    }

    public function onElementStart($element_qname, array $attributes)
    {
        if (preg_match('/^([^:]+):/', $element_qname, $m)) {
            $prefix = $m[1];
            $namespace_uri = $this->prefixToNamespaceURI($prefix, $attributes);
        } else {
            $prefix = '';
            $unprefixed_parent = $this->findParentWithoutPrefix($this->_current);
            if ($unprefixed_parent) {
                $namespace_uri = $unprefixed_parent->namespaceURI;
            } else {
                $namespace_uri = $this->_current->lookupNamespaceURI(NULL);
            }
        }

        if (false === $namespace_uri) {
            throw new PHPTAL_ParserException("There is no namespace declared for prefix of element < $element_qname >");
        }

        $element = $this->document->createElementNS($namespace_uri, $element_qname);

        foreach ($attributes as $qname=>$encoded_value) {
            if (preg_match('/^([^:]+):(.+)$/', $qname, $m)) {
                $local_name = $m[2];
                $attr_namespace_uri = $this->prefixToNamespaceURI($m[1], $attributes);
            } else {
                $local_name = $qname;
                $attr_namespace_uri = $this->_current->lookupNamespaceURI(NULL); // default NS. Attributes don't inherit namespace per XMLNS spec
            }

            if (false === $attr_namespace_uri) {
                throw new PHPTAL_ParserException("There is no namespace declared for prefix of attribute $qname of element < $element_qname >");
            }

            $defs = PHPTAL_Dom_Defs::getInstance();
            if ($defs->isHandledNamespace($attr_namespace_uri)
                && !$defs->isValidAttributeNS($attr_namespace_uri, $local_name)) {
                throw new PHPTAL_ParserException("Attribute '$qname' is in '$attr_namespace_uri' namespace, but is not a supported PHPTAL attribute");
            }

            $element->setAttributeNS($attr_namespace_uri, $qname, $this->decodeEntities($encoded_value));
        }

        $this->_stack[] =  $this->_current;
        $this->_current->appendChild($element);
        assert('$element_qname === $this->getQName($element)');
        assert('$element->namespaceURI === $namespace_uri'); 
        $this->_current = $element;

    }

    public function onElementData($data)
    {
        $this->_current->appendChild($this->document->createTextNode($this->decodeEntities($data)));
    }

    private function getQName(DOMElement $element)
    {
        if ($element->prefix !== '') return $element->prefix.':'.$element->localName;
        return $element->localName;
    }

    public function onElementClose($qname)
    {
        if (!count($this->_stack)) {
            throw new PHPTAL_ParserException("Found closing tag for < $qname > where there are no open tags");
        }

        $current_qname = $this->getQName($this->_current);

        if ($current_qname != $qname) {
            throw new PHPTAL_ParserException("Tag closure mismatch, expected < /".$current_qname." > but found < /".$qname." >");
        }
        $this->_current = array_pop($this->_stack);
    }

    public function setEncoding($encoding)
    {
        if (strtoupper($encoding) !== 'UTF-8') {
            throw new PHPTAL_ConfigurationException("PHPTAL with PHP5 DOM pre-filters supports only UTF-8 encoding ($encoding was set)");
        }
    }
}

