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
 * @version  SVN: $Id:$
 * @link     http://phptal.org/
 */


class HTMLGeneratorTest extends PHPTAL_TestCase {

    function testTalDoesntConsumeNewline()
    {
        $res = $this->newPHPTAL()->setSource('<tal:block tal:condition="php:true">I\'m on a line</tal:block>
<tal:block tal:condition="php:true">I\'m on a line</tal:block>')->execute();

        $this->assertEquals('I\'m on a line
I\'m on a line', $res);
    }

    function testPHPConsumesNewline()
    {
        $res = $this->newPHPTAL()->setSource('<p><?php echo ""; ?>
No new line<?php echo ""; ?>
No new line</p>')->execute();

        $this->assertEquals("<p>No new lineNo new line</p>", $res);
    }

}
