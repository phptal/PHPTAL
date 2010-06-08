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


class CommentFilterTest extends PHPTAL_TestCase
{
    function testStripComments()
    {
        $t = $this->newPHPTAL('input/comment-filter-01.html');
        $t->addPreFilter(new PHPTAL_PreFilter_StripComments());
        $res = $t->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/comment-filter-01.html');
        $this->assertEquals($exp, $res);
    }

    function testPreservesScript()
    {
        $t = $this->newPHPTAL();
        $t->addPreFilter(new PHPTAL_PreFilter_StripComments());
        $t->setSource('<script>//<!--
        alert("1990s called"); /* && */
        //--></script>');

        $this->assertEquals(normalize_html('<script>//<![CDATA[
        alert("1990s called"); /* && */
        //]]></script>'), normalize_html($t->execute()));
    }

    function testNamespaceAware()
    {
        $t = $this->newPHPTAL();
        $t->addPreFilter(new PHPTAL_PreFilter_StripComments());
        $t->setSource('<script xmlns="http://example.com/foo">//<!--
        alert("1990s called"); /* && */
        //--></script>');

        $this->assertEquals(normalize_html('<script xmlns="http://example.com/foo">//</script>'), normalize_html($t->execute()));
    }
}
