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


class CommentTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $source = '<html><!-- \${variable} --></html>';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->assertEquals($source, $res);
    }

    function testNoEntities()
    {
        $source = '<html><!-- <foo> --></html>';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source, __FILE__);
        $res = $tpl->execute();
        $this->assertEquals($source, $res);
    }

    function testShortComments()
    {
        $source = '<html><!--><--></html>';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->assertEquals($source, $res);
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testNestedComments()
    {
        $source = '<html><!--<!--<!--></html>';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->fail("Ill-formed comment accepted");
    }

    /**
     * @expectedException PHPTAL_ParserException
     */
    function testDashedComment()
    {
        $source = '<html><!--- XML hates you ---></html>';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->fail("Ill-formed comment accepted");
    }


    function testSkippedComments()
    {
        $source = '<html><!--!
        removed --><!-- left --><!-- !removed --></html>';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $res = $tpl->execute();
        $this->assertEquals('<html><!-- left --></html>', $res);
    }

    function testCStyleComments()
    {
        $tpl = $this->newPHPTAL();
        $src = '<script><!--
            // comment
            /* comment <tag> */
            // comment
            --></script>';
        $tpl->setSource($src);
        $this->assertEquals($src, $tpl->execute());
    }
}

