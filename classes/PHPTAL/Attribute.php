<?php

require_once 'PHPTAL/Attribute/TAL/Replace.php';
require_once 'PHPTAL/Attribute/TAL/Content.php';
require_once 'PHPTAL/Attribute/TAL/Condition.php';
require_once 'PHPTAL/Attribute/TAL/Attributes.php';
require_once 'PHPTAL/Attribute/TAL/Repeat.php';
require_once 'PHPTAL/Attribute/TAL/Define.php';
require_once 'PHPTAL/Attribute/TAL/OnError.php';
require_once 'PHPTAL/Attribute/TAL/OmitTag.php';

require_once 'PHPTAL/Attribute/METAL/DefineMacro.php';
require_once 'PHPTAL/Attribute/METAL/UseMacro.php';
require_once 'PHPTAL/Attribute/METAL/DefineSlot.php';
require_once 'PHPTAL/Attribute/METAL/FillSlot.php';

/**
 * @package PHPTAL
 */
abstract class PHPTAL_Attribute 
{
    public $name;
    public $expression;
    public $tag;

    public function __construct( $tag )
    {
        $this->tag = $tag;
    }

    public abstract function start();
    public abstract function end();
    
    public static function createAttribute( $tag, $attName, $expression )
    {
        $class = 'PHPTAL_Attribute_' . str_replace(':','_', $attName);
        $class = str_replace('-', '', $class);
        
        $result = new $class($tag);
        $result->name = $attName;
        $result->expression = $expression;
        return $result;
    }
}

?>
