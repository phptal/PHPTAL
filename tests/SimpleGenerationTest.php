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
 * @link     http://phptal.org/
 */



class SimpleGenerationTest extends PHPTAL_TestCase
{
    function testTreeGeneration()
    {
        $tpl = $this->newPHPTAL();

        $parser = new PHPTAL_Dom_SaxXmlParser($tpl->getEncoding());
        $treeGen = $parser->parseFile(new PHPTAL_Dom_PHPTALDocumentBuilder(), 'input/parser.01.xml')->getResult();
        $state     = new PHPTAL_Php_State($tpl);
        $codewriter = new PHPTAL_Php_CodeWriter($state);
        $codewriter->doFunction('test', '$tpl');
        $treeGen->generateCode($codewriter);
        $codewriter->doEnd('function');
        $result = $codewriter->getResult();

        $expected = <<<EOS
<?php
function test(\$tpl) {
\$ctx->setXmlDeclaration('<?xml version="1.0"?>',false) ;?>
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

        $result = normalize_phpsource($result, true);
        $expected = normalize_phpsource($expected, true);


        $this->assertEquals($expected, $result);
    }

    function testFunctionsGeneration()
    {
        $state = new PHPTAL_Php_State($this->newPHPTAL());
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
<?php function test2(\$tpl) {?>test2<?php}?>
<?php function test1(\$tpl) {?>test1test1<?php}?>
EOS;
        $res = normalize_phpsource($res, true);
        $exp = normalize_phpsource($exp, true);
        $this->assertEquals($exp, $res);
    }
}


