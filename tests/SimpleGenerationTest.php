
<?php

require_once 'config.php';
require_once 'PHPTAL/Parser.php';
require_once 'PHPTAL/CodeGenerator.php';

class SimpleGenerationTest extends PHPUnit2_Framework_TestCase
{
    function testTreeGeneration()
    {
        $generator = new PHPTAL_CodeGenerator();
        $parser = new PHPTAL_Parser( $generator );
        $tree = $parser->parseFile('input/parser.01.xml');
        $generator->doFunction('test', '$tpl');
        $tree->generate();
        $generator->doEnd();
        $result = $generator->getResult();

        // WILL FAIL LATER because php will try to interpret <?xml...
        $expected = <<<EOS
<?php 
function test( \$tpl ) {
\$tpl->setDocType('<?xml version="1.0"?>') ;
?>
<html>
  <head>
    <title>test document</title>
  </head>
  <body>
    <h1>test document</h1>
    <a href="http://phptal.sf.net">phptal</a>
  </body>
</html><?php
}

 ?>
EOS;
        $result = $this->trimCode($result);
        $expected = $this->trimCode($expected);
        $this->assertEquals($result, $expected);
    }

    function testFunctionsGeneration()
    {
        $generator = new PHPTAL_CodeGenerator();
        $generator->doFunction('test1', '$tpl');
        $generator->pushString('test1');
        $generator->doFunction('test2', '$tpl');
        $generator->pushString('test2');
        $generator->doEnd();
        $generator->pushString('test1');
        $generator->doEnd();
        $res = $generator->getResult();
        $exp = <<<EOS
<?php function test2( \$tpl ) {?>test2<?php}?>
<?php function test1( \$tpl ) {?>test1test1<?php}?>
EOS;
        $res = $this->trimCode($res);
        $exp = $this->trimCode($exp);
        $this->assertEquals($exp, $res);
    }


    function trimCode( $code )
    {
        $lines = split("\n", $code);
        $code = "";
        foreach ($lines as $line){
            $code .= trim($line);
        }
        return $code;
    }
}

?>
