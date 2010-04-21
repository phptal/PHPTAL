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

require_once 'PHPTAL/Dom/DocumentBuilder.php';

/**
 * DOM Builder
 *
 * @package PHPTAL
 * @subpackage Dom
 */
class PHPTAL_Dom_PHP5DOMDocumentBuilder extends PHPTAL_Dom_DocumentBuilder
{
    private $document;
    
    public function getResult()
    {
        return $this->document;
    }


    // ~~~~~ XmlParser implementation ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    public function onDocumentStart()
    {
        $this->document = new DOMDocument();
        
        $root = $this->document->createElementNS('http://xml.zope.org/namespaces/tal','tal:documentElement');
        $this->document->appendChild($root);
        
        $this->_current = $this->document->documentElement;
    }

    public function onDocumentEnd()
    {
        if (count($this->_stack) > 0) {
            $left='</'.$this->_current->localName.'>';
            for($i = count($this->_stack)-1; $i>0; $i--) $left .= '</'.$this->_stack[$i]->localName.'>';
            throw new PHPTAL_ParserException("Not all elements were closed before end of the document. Missing: ".$left);
        }
    }

    public function onDocType($doctype_string)
    {
        // FIXME
    }

    public function onXmlDecl($decl)
    {
        // FIXME
    }

    public function onComment($data)
    {
        $this->document->create($this->document->createComment($data));
    }

    public function onCDATASection($data)
    {
        $this->document->create($this->document->createCDATASection($data));
    }

    public function onProcessingInstruction($data)
    {
        $this->document->create($this->document->createProcessingInstruction('FIXME',$data));
    }
    
    private function decodeEntities($str)
    {
        return html_entity_decode($attributes['xmlns'], ENT_QUOTES,'UTF-8');
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
        
        return PHPTAL_Dom_Defs::getInstance()->prefixToNamespaceURI($prefix);
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
        $this->document->create($element);
        $this->_stack[] =  $this->_current;
        $this->_current = $element;
    }

    public function onElementData($data)
    {
        $this->document->create($this->document->createTextNode($this->decodeEntities($data)));
    }

    public function onElementClose($qname)
    {
        if ($this->_current === $this->document->documentElement) {
            throw new PHPTAL_ParserException("Found closing tag for < $qname > where there are no open tags");
        }
        
        $current_qname = $this->_current->localName;
        if ($this->_current->prefix !== NULL) $current_qname = $this->_current->prefix.':'.$current_qname;
        
        if ($current_qname != $qname) {
            throw new PHPTAL_ParserException("Tag closure mismatch, expected < /".$current_qname." > but found < /".$qname." >");
        }
        $this->_current = array_pop($this->_stack);        
    }

    public function setEncoding($encoding)
    {
        if (strtoupper($encoding) !== 'UTF-8') {
            throw new PHPTAL_ParserException("PHPTAL with PHP5 DOM pre-filters supports only UTF-8 encoding ($encoding was set)");
        }
    }
}

