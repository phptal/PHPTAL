<?php

require_once 'config.php';
require_once 'PHPTAL/Parser.php';
require_once 'PHPTAL/CodeGenerator.php';

class ParserTest extends PHPUnit2_Framework_TestCase 
{
    function testParseSimpleDocument()
    {
        $generator = new PHPTAL_CodeGenerator();
        $parser = new PHPTAL_Parser( $generator );
        $tree = $parser->parseFile('input/parser.01.xml');
        $this->assertEquals(3, count($tree->children));
        $this->assertEquals(5, count($tree->children[2]->children));
    }
}

?>
