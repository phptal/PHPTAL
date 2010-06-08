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


class TalesModeTest extends PHPTAL_TestCase
{
    public function testUnsupportedMode()
    {
        try {
            $tpl = $this->newPHPTAL('input/tales.mode.01.xml');
            $tpl->execute();
            $this->assertTrue(false);
        }
        catch (PHPTAL_Exception $e)
        {
            $this->assertTrue(true);
        }
    }
}
