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

class PhptalPathTest_DummyClass
{
    public $foo;
}

/* protected get/isset doesn't work in PHP 5.3
class PhptalPathTest_DummyIssetClass
{
    protected function __isset($isset)
    {
        return false;
    }
}

class PhptalPathTest_DummyGetClass
{
    protected function __get($anything)
    {
        return 'whatever';
    }
}
*/

require_once dirname(__FILE__)."/config.php";

class PhptalPathTest extends PHPTAL_TestCase
{
    function testZeroIndex()
    {
        $data   = array(1, 0, 3);
        $result = phptal_path($data, '0');
        $this->assertEquals(1, $result);
    }

    /* protected get/isset doesn't work in PHP 5.3
    function testProtectedIsset()
    {
        $tpl = $this->newPHPTAL();
        $tpl->protected = new PhptalPathTest_DummyIssetClass;
        $tpl->setSource('<p tal:content="protected/fail | \'ok\'"></p>');
        $res = $tpl->execute();
        $this->assertEquals($res,'<p>ok</p>');
    }

    function testProtectedGet()
    {
        $tpl = $this->newPHPTAL();
        $tpl->protected = new PhptalPathTest_DummyGetClass;
        $tpl->setSource('<p tal:content="protected/fail | \'ok\'"></p>');
        $res = $tpl->execute();
        $this->assertEquals($res,'<p>ok</p>');
    }
    */

    function testDefinedButNullProperty()
    {
        $src = <<<EOS
<span tal:content="o/foo"/>
<span tal:content="o/foo | string:blah"/>
<span tal:content="o/bar" tal:on-error="string:ok"/>
EOS;
        $exp = <<<EOS
<span></span>
<span>blah</span>
ok
EOS;

        $tpl = $this->newPHPTAL();
        $tpl->setSource($src, __FILE__);
        $tpl->o = new PhptalPathTest_DummyClass();
        $res = $tpl->execute();

        $this->assertEquals($exp, $res);
    }
}


