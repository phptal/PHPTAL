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

class CommentFilterTest extends PHPTAL_TestCase
{
    function testStripComments() {
        $t = $this->newPHPTAL('input/comment-filter-01.html');
        $t->addPreFilter("strip_comments");
        $res = $t->execute();
        $res = trim_string($res);
        $exp = trim_file('output/comment-filter-01.html');
        $this->assertEquals($exp,$res);
    }

    function testPreservesScript() {
        $t = $this->newPHPTAL();
        $t->addPreFilter("strip_comments");
        $t->setSource('<script>//<!--
        alert("1990s called"); /* && */
        //--></script>');
        
        $this->assertEquals(trim_string('<script>//<![CDATA[
        alert("1990s called"); /* && */
        //]]></script>'),trim_string($t->execute()));
    }

    function testNamespaceAware() {
        $t = $this->newPHPTAL();
        $t->addPreFilter("strip_comments");
        $t->setSource('<script xmlns="http://example.com/foo">//<!--
        alert("1990s called"); /* && */
        //--></script>');
        
        $this->assertEquals(trim_string('<script xmlns="http://example.com/foo">//</script>'),trim_string($t->execute()));
    }
}
