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


class ContentInterpolationTest extends PHPTAL_TestCase
{
    public function testInterpol()
    {
        $src = <<<EOT
<span>\${foo}</span>
EOT;
        $exp = <<<EOT
<span>foo value</span>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    public function testInterpol2()
    {
        $src = <<<EOT
<span>\${foo2} x \${structure foo} y \${foo}\${structure foo2}</span><img/>
EOT;
        $exp = <<<EOT
<span>{foo2 &lt;img /&gt;} x foo value y foo value{foo2 <img />}</span><img/>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $tpl->foo2 = '{foo2 <img />}';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    public function testInterpol3()
    {
        $src = <<<EOT
<span>\${foo}\${foo}</span>
EOT;
        $exp = <<<EOT
<span>foo valuefoo value</span>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    public function testNoInterpol()
    {
        $src = <<<EOT
<span>$\${foo}</span>
EOT;
        $exp = <<<EOT
<span>\${foo}</span>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    public function testInterpolAdv()
    {
        $src = <<<EOT
<span>$$\${foo}</span>
EOT;
        $exp = <<<EOT
<span>\$foo value</span>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }


    function testUnescape()
    {
        $tpl = $this->newPHPTAL();

        $tpl->var = 'val<';

        $tpl->setSource('<p>
            ${var}

            $${var}

            $$${var}

            $$$${var}
        </p>');

        $this->assertEquals(normalize_html('<p>
            val&lt;

            ${var}

            $val&lt;

            $${var}
        </p>'), normalize_html($tpl->execute()));
    }

    function testUnescapeString()
    {
        $tpl = $this->newPHPTAL();

        $tpl->var = 'val<';

        $tpl->setSource('<p tal:content="string:
             ${var}

             $${var}

             $$${var}

             $$$${var}
         "/>');

        $this->assertEquals(normalize_html('<p>
             val&lt;

             ${var}

             $val&lt;

             $${var}
         </p>'), normalize_html($tpl->execute()));
    }

    function testUnescapeStructure()
    {
        $tpl = $this->newPHPTAL();

        $tpl->var = 'val<x/>';

        $tpl->setSource('<p>
            ${structure var}

            $${structure var}

            $$${structure var}

            $$$${structure var}
        </p>');

        $this->assertEquals(normalize_html('<p>
            val<x/>

            ${structure var}

            $val<x/>

            $${structure var}
        </p>'), normalize_html($tpl->execute()));
    }

    function testUnescapeCDATA()
    {
        $tpl = $this->newPHPTAL();

        $tpl->var = 'val<';

        $tpl->setSource('<script><![CDATA[<
            ${text var}

            $${text var}

            $$${var}

            $$$${var}
        ]]></script>');

        $this->assertEquals(normalize_html('<script><![CDATA[<
            val<

            ${text var}

            $val<

            $${var}
        ]]></script>'), normalize_html($tpl->execute()));
    }

    function testUnescapeCDATAStructure()
    {
        $tpl = $this->newPHPTAL();

        $tpl->var = 'val<';

        $tpl->setSource('<script><![CDATA[<
            ${structure var}

            $${structure var}

            $$${structure var}

            $$$${structure var}
        ]]></script>');

        $this->assertEquals(normalize_html('<script><![CDATA[<
            val<

            ${structure var}

            $val<

            $${structure var}
        ]]></script>'), normalize_html($tpl->execute()));
    }

    function testUnescapePHPTales()
    {
        $tpl = $this->newPHPTAL();

        $tpl->var = '1';

        $tpl->setSource('<p phptal:tales="php">
            ${var+1}

            $${var+1}

            $$${var+1}

            $$$${var+1}
        </p>');

        $this->assertEquals(normalize_html('<p>
            2

            ${var+1}

            $2

            $${var+1}
        </p>'), normalize_html($tpl->execute()));
    }

    public function testPHPBlock()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>test<?php echo "<x>"; ?>test<?php print("&amp;") ?>test</p>');
        $this->assertEquals('<p>test<x>test&amp;test</p>', $tpl->execute());
    }

    public function testPHPBlockShort()
    {
        ini_set('short_open_tag', 1);
        if (!ini_get('short_open_tag')) $this->markTestSkipped("PHP is buggy");

        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>test<? print("<x>"); ?>test<?= "&amp;" ?>test</p>');
        $this->assertEquals('<p>test<x>test&amp;test</p>', $tpl->execute());
        ini_restore('short_open_tag');
    }

    public function testPHPBlockNoShort()
    {
        ini_set('short_open_tag', 0);
        if (ini_get('short_open_tag')) $this->markTestSkipped("PHP is buggy");

        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>test<? print("<x>"); ?>test<?= "&amp;" ?>test</p>');
        try
        {
            if (version_compare(PHP_VERSION, '5.4.0') < 0) {
                // unlike attributes, this isn't going to be escaped, because it gets parsed as a real processing instruction
                $this->assertEquals('<p>test<? print("<x>"); ?>test<?= "&amp;" ?>test</p>', $tpl->execute());
            }
            else {
                // PHP 5.4: short tag <?= is always enabled.
                $this->assertEquals('<p>test<? print("<x>"); ?>test&amp;test</p>', $tpl->execute());
            }
        }
        catch(PHPTAL_ParserException $e) {/* xml ill-formedness error is ok too */}
        ini_restore('short_open_tag');
    }

    /**
     * @expectedException PHPTAL_VariableNotFoundException
     */
    function testErrorsThrow()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>${error}</p>');
        $tpl->execute();
    }

    /**
     * @expectedException PHPTAL_VariableNotFoundException
     */
    function testErrorsThrow2()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>${error | error}</p>');
        $tpl->execute();
    }

    /**
     * @expectedException PHPTAL_VariableNotFoundException
     */
    function testErrorsThrow3()
    {
        $tpl = $this->newPHPTAL();
        $tpl->foo = array();
        $tpl->setSource('<p>${foo/error | foo/error}</p>');
        $tpl->execute();
    }

    function testErrorsSilenced()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>${error | nothing}</p>');
        $this->assertEquals('<p></p>', $tpl->execute());
    }

    function testZeroIsNotEmpty()
    {
        $tpl = $this->newPHPTAL();
        $tpl->zero = '0';
        $tpl->setSource('<p>${zero | error}</p>');
        $this->assertEquals('<p>0</p>', $tpl->execute());
    }

    function testPreservesNewline()
    {
        $tpl = $this->newPHPTAL()->setSource('<body>
${variable1 | string:Line 1}
<tal:block tal:content="variable2 | string:Line 2"></tal:block>
Line 3
</body>');

        $this->assertEquals('<body>
Line 1
Line 2
Line 3
</body>', $tpl->execute(), $tpl->getCodePath());
    }

    function testMultilineInterpolationPHP()
    {
        $res = $this->newPHPTAL()->setSource('<p>${php:\'foo
        bar\'}</p>')->execute();

        $this->assertEquals('<p>foo
        bar</p>', $res);

        $res = $this->newPHPTAL()->setSource('<p>${php:\'foo\' .
        substr(\'barz\' ,
        0,3)}</p>')->execute();

        $this->assertEquals('<p>foobar</p>', $res);
    }


    function testMultilineInterpolation()
    {
        $res = $this->newPHPTAL()->setSource('<p>${string:foo
        bar}</p>')->execute();

        $this->assertEquals('<p>foo
        bar</p>', $res);

        $res = $this->newPHPTAL()->setSource('<p>${structure string:foo
        bar}</p>')->execute();

        $this->assertEquals('<p>foo
        bar</p>', $res);
    }

    function testTagsBreakTALES()
    {
        $res = $this->newPHPTAL()->setSource('<p>${foo<br/>bar}</p>')->execute();

        $this->assertEquals('<p>${foo<br/>bar}</p>', $res);
    }

    function testEscapedTagsDontBreakTALES()
    {
        $res = $this->newPHPTAL()->setSource('<p>${structure string:foo&lt;br  />bar}</p>')->execute();

        $this->assertEquals('<p>foo<br  />bar</p>', $res);
    }
}
