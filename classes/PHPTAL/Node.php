<?php

require_once 'PHPTAL/CodeGenerator.php';
require_once 'PHPTAL/Defs.php';
require_once 'PHPTAL/Attribute.php';

/**
 * @package PHPTAL
 */
abstract class PHPTAL_Node
{
    public $line;
    public $parser;
    public $generator;

    public function __construct( $parser )
    {
        $this->parser = $parser;
        $this->generator = $parser->getGenerator();
        $this->line = $parser->getLineNumber();
    }

    public abstract function generate();
}

/**
 * @package PHPTAL
 */
class PHPTAL_NodeTree extends PHPTAL_Node
{
    public $children;

    public function __construct( $parser )
    {
        parent::__construct($parser);
        $this->children = array();
    }

    public function generate()
    {
        foreach ($this->children as $child) 
            $child->generate();
    }
}

/**
 * @package PHPTAL
 */
class PHPTAL_NodeElement extends PHPTAL_NodeTree
{
    const ERR_ATTRIBUTES_CONFLICT =
        "Attribute conflict in '%s' at line '%d', '%s' cannot appear with '%s'";
    
    public $name;
    public $attributes = array();
    public $talAttributes = array();

    public $replaceAttributes = array();
    public $contentAttributes = array();
    public $surroundAttributes = array();

    public $headFootDisabled = false;

    public function __construct( $parser, $name, $attributes )
    {
        parent::__construct($parser);
        $this->name = $name;
        $this->attributes = $attributes;
    }

    public function generate()
    {
        $this->prepareAttributes();
        $this->separateAttributes();
        $this->orderTalAttributes();
       
        if (defined('PHPTAL_DEBUG')){
            $this->generator->doComment("tag '$this->name' from line $this->line");
        }
        
        if (count($this->replaceAttributes) > 0) {
            $this->generateSurroundHead();
            foreach ($this->replaceAttributes as $att) {
                $att->start( $this );
                $att->end( $this );
            }
            $this->generateSurroundFoot();
            return;
        }

        $this->generateSurroundHead();
        $this->generateHead();
        $this->generateContent();
        $this->generateFoot();
        $this->generateSurroundFoot();
    }

    public function generateSurroundHead()
    {
        foreach ($this->surroundAttributes as $att) {
            $att->start( $this );
        }
    }

    public function generateSurroundFoot()
    {
        for ($i = (count($this->surroundAttributes)-1); $i>= 0; $i--) {
            $this->surroundAttributes[$i]->end( $this );
        }
    }

    public function generateHead()
    {
        if ($this->headFootDisabled) return;
        $this->generator->pushHtml('<'.$this->name);
        $this->generateAttributes();

        if ($this->hasContent()) {
            $this->generator->pushHtml('>');
        }
        else {
            $this->generator->pushHtml('/>');
        }
    }
    
    public function generateContent( $realContent=false )
    {
        if (!$realContent && count($this->contentAttributes) > 0) {
            foreach ($this->contentAttributes as $att) {
                $att->start( $this );
                $att->end( $this );
            }
            return;
        }
        parent::generate();
    }

    private function generateAttributes()
    {
        // A phptal attribute can modify any node attribute replacing
        // its value by a <?= $somevalue ?\ >.
        //
        // The entire attribute (key="value") can be replaced using the
        // '$__att_' value code, it is very usefull for xhtml boolean
        // attributes like selected, checked, etc...
        //
        // example: 
        //  
        //  $tag->generator->pushCode(
        //  '$__att_checked = $somecondition ? \'checked="checked"\' : \'\''
        //  );
        //  $tag->attributes['checked'] = '<?= $__att_checked ?\>';
        // 

        foreach ($this->attributes as $key=>$value) {
            if (preg_match('/<\?= \$__att_.*? \?>/', $value)) { 
                $this->generator->pushHtml($value);
            }
            else {
                $this->generator->pushHtml(' '.$key.'="'.$value.'"');
            }
        }
    }

    public function generateFoot()
    {
        if ($this->headFootDisabled) return;
        if ($this->hasContent() == false) return;
        $this->generator->pushHtml( '</'.$this->name.'>' );
    }

    private function hasContent()
    {
        return count($this->children) > 0 || count($this->contentAttributes) > 0;
    }

    private function prepareAttributes()
    {
        if (preg_match('/^(.*?):block$/', $this->name, $m)) {
            $this->headFootDisabled = true;
            list(,$ns) = $m;
            $attributes = array();
            foreach ($this->attributes as $key=>$value) {
                if (PHPTAL_Defs::isValidAttribute("$ns:$key")) {
                    $attributes["$ns:$key"] = $value;
                }
                else {
                    $attributes[$key] = $value;
                }
            }
            $this->attributes = $attributes;
        }
    }

    private function separateAttributes()
    {
        $attributes = array();
        $this->talAttributes = array();
        foreach ($this->attributes as $key=>$value) {
            // remove handled xml namespaces
            if (PHPTAL_Defs::isHandledXmlNs($key)){
            }
            else if (PHPTAL_Defs::isPhpTalAttribute($key)) {
                $this->talAttributes[$key] = $value;
            }
            else if (PHPTAL_Defs::isBooleanAttribute($key)) {
                $attributes[$key] = $key;
            }
            else {
                $attributes[$key] = $value;
            }
        }
        $this->attributes = $attributes;
    }

    private function orderTalAttributes()
    {
        $result = array();
        foreach ($this->talAttributes as $key=>$exp) {
            $pos = PHPTAL_Defs::$RULES_ORDER[ strtoupper($key) ];
            if (array_key_exists($pos, $result)) {
                $err = sprintf(self::ERR_ATTRIBUTES_CONFLICT, 
                               $this->name, $this->line, $key, $result[$pos]->name);
                throw new Exception($err);
            }
            $result[$pos] = PHPTAL_Attribute::createAttribute( $this, $key, $exp );
        }

        $this->talHandlers = $result;
        foreach ($result as $handler) {
            $type = PHPTAL_Defs::$DICTIONARY[strtoupper($handler->name)];
            switch ($type) {
                case PHPTAL_Defs::REPLACE:
                    $this->replaceAttributes[] = $handler;
                    break;
                    
                case PHPTAL_Defs::SURROUND:
                    $this->surroundAttributes[] = $handler;
                    break;
                    
                case PHPTAL_Defs::CONTENT:
                    $this->contentAttributes[] = $handler;
                    break;
            }
        }
    }
}

/**
 * @package PHPTAL
 */
class PHPTAL_NodeText extends PHPTAL_Node
{
    public $value;

    public function __construct( $parser, $data )
    {
        parent::__construct($parser);
        $this->value = $data;
    }
    
    public function generate()
    {
        $this->generator->pushString($this->value);
    }
}

/**
 * @package PHPTAL
 */
class PHPTAL_NodeSpecific extends PHPTAL_Node
{
    public $value;

    public function __construct( $parser, $data )
    {
        parent::__construct($parser);
        $this->value = $data;
    }

    public function generate()
    {
        $this->generator->pushHtml($this->value);
    }
}

class PHPTAL_NodeDoctype extends PHPTAL_Node
{
    public $value;

    public function __construct( $parser, $data )
    {
        parent::__construct($parser);
        $this->value = $data;
    }

    public function generate()
    {
        $code = sprintf('$tpl->setDocType(\'%s\')', str_replace('\'', '\\\'', $this->value));
        $this->generator->pushCode($code);
        $this->generator->setDocType($this->value);
    }
}

?>
