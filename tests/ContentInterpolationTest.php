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

    public function testNoInterpolAdv()
    {
        $src = <<<EOT
<span>$$\${foo}</span>
EOT;
        $exp = <<<EOT
<span>$\${foo}</span>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }

    public function testPHPBlock()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>test<?php echo "<x>"; ?>test<?php print("&amp;") ?>test</p>');
        $this->assertEquals('<p>test<x>test&amp;test</p>', $tpl->execute());
}

    public function testPHPBlockShort()
    {
        ini_set('short_open_tag',1);
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
            // unlike attributes, this isn't going to be escaped, because it gets parsed as a real processing instruction
            $this->assertEquals('<p>test<? print("<x>"); ?>test<?= "&amp;" ?>test</p>', $tpl->execute());
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

    function testErrorsSilenced()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<p>${error | nothing}</p>');
        $this->assertEquals('<p></p>',$tpl->execute());
    }

    function testZeroIsNotEmpty()
    {
        $tpl = $this->newPHPTAL();
        $tpl->zero = '0';
        $tpl->setSource('<p>${zero | error}</p>');
        $this->assertEquals('<p>0</p>',$tpl->execute());
    }
}
