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

    public function getResult()
    {
        return $this->document->documentElement;
    }


    // ~~~~~ XmlParser implementation ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public function onDocumentStart()
    {
        $this->document = new DOMDocument('1.0','UTF-8');

        $this->root = $this->document->createDocumentFragment();
        $this->_current = $this->root;
    }

    public function onDocumentEnd()
    {
        $this->document->appendChild($this->root);

        if (count($this->_stack) > 0) {
            $left='</'.$this->getQName($this->_current).'>';
            for ($i = count($this->_stack)-1; $i>0; $i--) $left .= '</'.$this->getQName($this->_stack[$i]).'>';
            throw new PHPTAL_ParserException("Not all elements were closed before end of the document. Missing: ".$left);
        }
    }

    public function onDocType($doctype_string)
    {
        // FIXME
    }

    public function onXmlDecl($decl)
    {
        if (preg_match('/\s+standalone\s*=\s*["\']yes/',$decl)) {
            $this->document->xmlStandalone = true;
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
        // FIXME: string lookupNamespaceURI ( string $prefix )
        if (isset($attributes['xmlns:'.$prefix])) {
            return trim($this->decodeEntities($attributes['xmlns:'.$prefix]));
        }

        $res = PHPTAL_Dom_Defs::getInstance()->prefixToNamespaceURI($prefix);
        return $res;
    }

    public function onElementStart($element_qname, array $attributes)
    {
        if (preg_match('/^([^:]+):/', $element_qname, $m)) {
            $prefix = $m[1];
        } else {
            $prefix = ''; $local_name = $element_qname;
        }

        $namespace_uri = $this->prefixToNamespaceURI($prefix, $attributes);

        if (false === $namespace_uri) {
            throw new PHPTAL_ParserException("There is no namespace declared for prefix of element < $element_qname >");
        }

        $element = $this->document->createElementNS($namespace_uri, $element_qname);


        // FIXME: xmlns first?
        foreach ($attributes as $qname=>$encoded_value) {

            if ($qname === 'xmlns') continue; // xmlns of the element is set via createElementNS

            if (preg_match('/^([^:]+):(.+)$/', $qname, $m)) {
                $local_name = $m[2];
                $attr_namespace_uri = $this->prefixToNamespaceURI($m[1], $attributes);

                if (false === $attr_namespace_uri) {
                    throw new PHPTAL_ParserException("There is no namespace declared for prefix of attribute $qname of element < $element_qname >");
                }
            } else {
                $local_name = $qname;
                $attr_namespace_uri = ''; // default NS. Attributes don't inherit namespace per XMLNS spec
            }

            if (PHPTAL_Dom_Defs::getInstance()->isHandledNamespace($attr_namespace_uri)
                && !PHPTAL_Dom_Defs::getInstance()->isValidAttributeNS($attr_namespace_uri, $local_name)) {
                throw new PHPTAL_ParserException("Attribute '$local_name' is in '$attr_namespace_uri' namespace, but is not a supported PHPTAL attribute");
            }

            $element->setAttributeNS($attr_namespace_uri, $qname, $this->decodeEntities($encoded_value));
        }

        $this->_stack[] =  $this->_current;
        $this->_current->appendChild($element);
        assert('$element_qname === $this->getQName($element)');

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

