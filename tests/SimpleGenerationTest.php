<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.motion-twin.com/ 
 */
class SimpleGenerationTest extends PHPTAL_TestCase
{
    function testTreeGeneration()
    {
        $parser = new PHPTAL_XmlParser('UTF-8');
        $treeGen = $parser->parseFile(new PHPTAL_DOM_DocumentBuilder(),'input/parser.01.xml')->getResult();
        $state     = new PHPTAL_Php_State();
        $codewriter = new PHPTAL_Php_CodeWriter($state);        
        $codewriter->doFunction('test', '$tpl');
        $treeGen->generate($codewriter);
        $codewriter->doEnd();
        $result = $codewriter->getResult();

        $expected = <<<EOS
<?php 
function test( \$tpl ) {
\$ctx->setXmlDeclaration('<?xml version="1.0"?>') ;?>
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
        $state = new PHPTAL_Php_State();
        $codewriter = new PHPTAL_Php_CodeWriter($state);
        $codewriter->doFunction('test1', '$tpl');
        $codewriter->pushHTML($codewriter->interpolateHTML('test1'));
        $codewriter->doFunction('test2', '$tpl');
        $codewriter->pushHTML('test2');
        $codewriter->doEnd();
        $codewriter->pushHTML('test1');
        $codewriter->doEnd();
        $res = $codewriter->getResult();
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
        $lines = explode("\n", $code);
        $code = "";
        foreach ($lines as $line) {
            $code .= trim($line);
        }
        return $code;
    }
}

?>
