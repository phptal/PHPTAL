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
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

class TalesExistTest extends PHPTAL_TestCase 
{
    function testLevel1()
    {
        $tpl = $this->newPHPTAL('input/tales-exist-01.html');
        $tpl->foo = 1;
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-exist-01.html');
        $this->assertEquals($exp, $res);
    }

    function testLevel2()
    {
        $o = new StdClass();
        $o->foo = 1;
        $tpl = $this->newPHPTAL('input/tales-exist-02.html');
        $tpl->o = $o;
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tales-exist-02.html');
        $this->assertEquals($exp, $res);
    }
}

?>
