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

class TalesPhpWithReplaceTest extends PHPTAL_TestCase
{
    function testIt()
    {
        $tpl = $this->newPHPTAL('input/talesphpwithreplace.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/talesphpwithreplace.01.html');
        $this->assertEquals($exp, $res);
    }
}

?>
