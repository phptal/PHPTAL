<?php

class PHPTAL_Dom_PHP5DOMConverter
{
    private $builder;
    private $file, $line_number;

    function __construct(PHPTAL_Dom_DocumentBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param string $xmldecl xml declaration <?xml …?>
     */
    final function convertDocument(DOMElement $root, $xmldecl)
    {
        $doc = $root->ownerDocument;
        $this->file = $root->ownerDocument->documentURI;
        if (!$this->file) $this->file = 'Converted DOM';
        $this->line_number = 1;

        $this->builder->setEncoding('UTF-8');
        $this->builder->onDocumentStart();

        if ($xmldecl) {
            $this->builder->onXmlDecl($xmldecl);
        }

        if ($doc->doctype) {
            $this->line_number++;

            // it seems that internalSubset isn't as documented in PHP. It's whole source.
            if ($doc->doctype->internalSubset !== null) {
                assert('0 === strpos($doc->doctype->internalSubset,"<!DOCTYPE ")');
                $doctype = $doc->doctype->internalSubset;
            } else {
                $doctype = '<!DOCTYPE '.$doc->doctype->name;

                if ($doc->doctype->publicId) {
                    $doctype .= ' PUBLIC "'.$doc->doctype->publicId.'"';
                } elseif ($doc->doctype->systemId) {
                    $doctype .= ' SYSTEM "'.$doc->doctype->systemId.'"';
                }
                // if ($doc->doctype->internalSubset !== null) {
                //     $doctype .= ' ['.$doc->doctype->internalSubset.']';
                // }
                $doctype .= '>';
            }
            $this->builder->onDocType($doctype);
        }

        $declared_prefixes = PHPTAL_Dom_Defs::getInstance()->getPredefinedPrefixes();
        $declared_prefixes[''] = $root->lookupNamespaceURI(NULL);
        $this->convertElement($root, $declared_prefixes);

        $this->builder->onDocumentEnd();

        return $this->builder;
    }

    /**
     * including no prefix
     */
    private function parentWithSamePrefix(DOMElement $element)
    {
        $prefix = $element->prefix;
        while($element = $element->parentNode) {
            if ($element->prefix === $prefix) return $element;
        }
        return NULL;
    }

    private function escape($text,$attr)
    {
        $escape = array('&'=>'&amp;','<'=>'&lt;', ']]>'=>']]&gt;');
        if ($attr) $escape += array('"' => '&quot;', "'"=>'&#39;');

        return strtr($text, $escape);
    }

    private function convertElement(DOMElement $element, array $declared_prefixes)
    {
        if (is_callable($element,'getLineNo') && $element->getLineNo()) { // added in 5.3
            $this->line_number = $element->getLineNo();
        }
        $this->builder->setSource($this->file, $this->line_number);

        foreach($element->attributes as $attr) {
            // keep track which namespaces have been declared
            if ($attr->prefix === 'xmlns' || ($attr->prefix === '' && $attr->localName === 'xmlns')) {
                $declared_prefixes[$attr->prefix] = $element->namespaceURI;
            }
        }

        $escaped_attributes = array();
        foreach($element->attributes as $attr) {
            $qname = $attr->localName;
            if ($attr->prefix !== '') {
                $qname = $attr->prefix . ':' . $qname;

                // only prefixed attributes are a problem
                if (!array_key_exists($attr->prefix,$declared_prefixes) || $declared_prefixes[$attr->prefix] !== $attr->namespaceURI) {
                    $escaped_attributes['xmlns:'.$attr->prefix] = $this->escape($attr->namespaceURI, true);
                }
            }

            $escaped_attributes[$qname] = $this->escape($attr->value, true);
        }

        if (!array_key_exists($element->prefix,$declared_prefixes) || $declared_prefixes[$element->prefix] !== $element->namespaceURI) {
            $escaped_attributes['xmlns' . ($element->prefix !== '' ? ':'.$element->prefix : '')] = $this->escape($element->namespaceURI, true);
        }

        $qname = $element->localName;
        if ($element->prefix !== '') $qname = $element->prefix . ':' . $qname;

        $this->builder->onElementStart($qname, $escaped_attributes);


        foreach($element->childNodes as $node) {
            switch($node->nodeType) {
                case XML_ELEMENT_NODE:
                    $this->convertElement($node, $declared_prefixes);
                break;
                case XML_CDATA_SECTION_NODE:
                    $this->builder->onCDATASection($node->data);
                    $this->addLines($node->data);
                break;
                case XML_PI_NODE:
                    $this->builder->onProcessingInstruction('<?'.$node->target . ' '. $node->data.'?>');
                    $this->addLines($node->data);
                break;
                case XML_COMMENT_NODE:
                    $this->builder->onComment($node->nodeValue);
                    $this->addLines($node->nodeValue);
                break;
                case XML_TEXT_NODE:
                    $this->builder->onElementData($this->escape($node->nodeValue, false));
                    $this->addLines($node->nodeValue);
                break;
                case XML_ENTITY_REF_NODE:
                    $this->builder->onElementData('&'.$node->nodeName.';');
                    break;
                default:
                    throw new PHPTAL_ParserException("Can't convert node type {$node->nodeType} (".get_class($node).')', $this->file, $this->line_number);
                break;
            }
        }
        $this->builder->onElementClose($qname);
    }

    /**
     * estimate when not available
     */
    private function addLines($text)
    {
        $this->line_number += substr_count($text,"\n");
    }
}
