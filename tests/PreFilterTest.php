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

class MyPreFilter implements PHPTAL_Filter
{
    public function filter($str)
    {
        if (preg_match('|<root>(.*?)</root>|s', $str, $m)) {
            return $m[1];
        }
        return $str;
    }
}

class MyPreFilter2 implements PHPTAL_Filter
{
    public function filter($str)
    {
        return preg_replace('/dummy/', '', $str);
    }
}

class PreFilterTest extends PHPTAL_TestCase
{
    function testIt()
    {
        $filter = new MyPreFilter();
        $tpl = $this->newPHPTAL('input/prefilter.01.html');
        $tpl->setPreFilter($filter);
        $tpl->value = 'my value';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/prefilter.01.html');
        $this->assertEquals($exp, $res);
    }


    function testExternalMacro()
    {
        $filter = new MyPreFilter2();
        $tpl = $this->newPHPTAL('input/prefilter.02.html');
        $tpl->setPreFilter($filter);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/prefilter.02.html');
        $this->assertEquals($exp, $res);
    }

    function testCache1()
    {
        $tpl = $this->newPHPTAL('input/prefilter.03.html');
        $tpl->execute(); // compile and store version without prefilter

        $tpl = $this->newPHPTAL('input/prefilter.03.html');
        $tpl->setPreFilter(new MyPreFilter2());
        $res = trim_string($tpl->execute());
        $exp = trim_string('<root>filtered</root>');
        $this->assertEquals($exp, $res);
    }

    function testCache2()
    {
        $tpl = $this->newPHPTAL('input/prefilter.03.html');
        $tpl->execute(); // prepare version without prefilter

        $tpl->setPreFilter(new MyPreFilter2());
        $res = trim_string($tpl->execute());
        $exp = trim_string('<root>filtered</root>');
        $this->assertEquals($exp, $res);
    }

}
