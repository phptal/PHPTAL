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

class DummyToStringObject
{
    public function __construct($value)
        { $this->_value = $value; }
    public function __toString()
        { return $this->_value; }
    private $_value;
}


class TalContentTest extends PHPTAL_TestCase
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/tal-content.01.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-content.01.html');
        $this->assertEquals($exp, $res);
    }

    function testVar()
    {
        $tpl = $this->newPHPTAL('input/tal-content.02.html');
        $tpl->content = 'my content';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-content.02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $tpl = $this->newPHPTAL('input/tal-content.03.html');
        $tpl->content = '<foo><bar/></foo>';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-content.03.html');
        $this->assertEquals($exp, $res);
    }

    function testNothing()
    {
        $tpl = $this->newPHPTAL('input/tal-content.04.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-content.04.html');
        $this->assertEquals($exp, $res);
    }

    function testDefault()
    {
        $tpl = $this->newPHPTAL('input/tal-content.05.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-content.05.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = $this->newPHPTAL('input/tal-content.06.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-content.06.html');
        $this->assertEquals($exp, $res);
    }

    function testEmpty()
    {
        $src = '
<root>
<span tal:content="nullv | falsev | emptystrv | zerov | default">default</span>
<span tal:content="nullv | falsev | emptystrv | default">default</span>
</root>
';
        $exp = '
<root>
<span>0</span>
<span>default</span>
</root>
';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->nullv = null;
        $tpl->falsev = false;
        $tpl->emptystrv = '';
        $tpl->zerov = 0;
        $res = $tpl->execute();
        $this->assertEquals(normalize_html($exp), normalize_html($res));
    }

    function testObjectEcho()
    {
        $foo = new DummyToStringObject('foo value');
        $src = <<<EOT
<root tal:content="foo"/>
EOT;
        $exp = <<<EOT
<root>foo value</root>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = $foo;
        $res = $tpl->execute();
        $this->assertEquals($res, $exp);
    }

    function testObjectEchoStructure()
    {
        $foo = new DummyToStringObject('foo value');
        $src = <<<EOT
<root tal:content="structure foo"/>
EOT;
        $exp = <<<EOT
<root>foo value</root>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = $foo;
        $res = $tpl->execute();
        $this->assertEquals($res, $exp);
    }

      /**
       * @expectedException PHPTAL_VariableNotFoundException
       */
      function testErrorsThrow()
      {
          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:content="erroridontexist"/>');
          $tpl->execute();
      }

      /**
       * @expectedException PHPTAL_VariableNotFoundException
       */
      function testErrorsThrow2()
      {
          $this->markTestSkipped("tal:define and tal:attributes rely on chains not throwing");//FIXME

          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:content="erroridontexist2 | erroridontexist2"/>');
          $tpl->execute();
      }

      /**
       * @expectedException PHPTAL_VariableNotFoundException
       */
      function testErrorsThrow3()
      {
          $this->markTestSkipped("tal:define and tal:attributes rely on chains not throwing");//FIXME

          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:replace="erroridontexist3 | erroridontexist3"/>');
          $tpl->execute();
      }

      /**
       * @expectedException PHPTAL_VariableNotFoundException
       */
      function testErrorsThrow4()
      {
          $this->markTestSkipped("tal:define and tal:attributes rely on chains not throwing");//FIXME

          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:condition="erroridontexist4 | erroridontexist4"/>');
          $tpl->execute();
      }

      function testErrorsSilenced()
      {
          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:content="erroridontexist | nothing"/>');
          $this->assertEquals('<p></p>',$tpl->execute());
      }

      function testZeroIsNotEmpty()
      {
          $tpl = $this->newPHPTAL();
          $tpl->zero = '0';
          $tpl->setSource('<p tal:content="zero | erroridontexist"/>');
          $this->assertEquals('<p>0</p>',$tpl->execute());
      }

      function testFalseLast()
      {
          $tpl = $this->newPHPTAL();
          $tpl->one_row = array('RESPONSIBLE_OFFICE'=>'responsible_office1');
          $tpl->setSource('<span tal:define="resp_office offices/${one_row/RESPONSIBLE_OFFICE} | false">${resp_office}</span>');

          $this->assertEquals('<span>0</span>',$tpl->execute());
      }
}

