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

require_once dirname(__FILE__)."/config.php";


PHPTAL::setIncludePath();
require_once 'PHPTAL/Dom/DocumentBuilder.php';
require_once 'PHPTAL/Php/CodeGenerator.php';
PHPTAL::restoreIncludePath();

if (!class_exists('DummyPhpNode')) {
    class DummyPhpNode extends PHPTAL_Dom_Element {
        function __construct() {}
        function generateCode(PHPTAL_Php_CodeWriter $codewriter) {}
    }
}

class TalCommentTest extends PHPTAL_TestCase
{
    function setUp()
    {
        parent::setUp();
        $state = new PHPTAL_Php_State();
        $this->_gen = new PHPTAL_Php_CodeWriter($state);
        $this->_tag = new DummyPhpNode();
        $this->_tag->codewriter = $this->_gen;
    }

    private function newComment($expr)
    {
        return $this->_att = new PHPTAL_Php_Attribute_TAL_Comment($this->_tag, $expr);
    }

    function testComment()
    {
        $this->newComment( 'my dummy comment');
        $this->_att->before($this->_gen);
        $this->_att->after($this->_gen);
        $res = $this->_gen->getResult();
        $this->assertEquals('<?php /* my dummy comment */; ?>', $res);
    }

    function testMultiLineComment()
    {
        $comment = "my dummy comment\non more than one\nline";
        $this->newComment($comment);
        $this->_att->before($this->_gen);
        $this->_att->after($this->_gen);
        $res = $this->_gen->getResult();
        $this->assertEquals("<?php /* $comment */; ?>", $res);
    }

    function testTrickyComment()
    {
        $comment = "my dummy */ comment\non more than one\nline";
        $this->newComment(  $comment);
        $this->_att->before($this->_gen);
        $this->_att->after($this->_gen);
        $res = $this->_gen->getResult();
        $comment = str_replace('*/', '* /', $comment);
        $this->assertEquals("<?php /* $comment */; ?>", $res);
    }

    function testInTemplate()
    {
        $tpl = $this->newPHPTAL('input/tal-comment.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-comment.01.html');
        $this->assertEquals($exp, $res);
    }

    function testMultilineInTemplate()
    {
        $tpl = $this->newPHPTAL('input/tal-comment.02.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-comment.02.html');
        $this->assertEquals($exp, $res);
    }

    private $_tag;
    private $_gen;
    private $_att;
}
