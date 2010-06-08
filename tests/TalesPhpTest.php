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


class TalesPhpTest extends PHPTAL_TestCase {

    function testMix()
    {
        $tpl = $this->newPHPTAL('input/php.html');
        $tpl->real = 'real value';
        $tpl->foo = 'real';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/php.html');
        $this->assertEquals($exp, $res);
    }

    function testPHPAttribute()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<foo bar="<?php  echo  \'baz\' ; ?>"/>');
        $this->assertEquals('<foo bar="baz"></foo>', $tpl->execute());
    }
}

