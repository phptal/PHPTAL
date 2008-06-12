<?php

class ContentInterpollationTest extends PHPUnit_Framework_Testcase
{
    public function testInterpol()
    {
        $src = <<<EOT
<span>\${foo}</span>
EOT;
        $exp = <<<EOT
<span>foo value</span>
EOT;
        $tpl = new PHPTAL();
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
        $tpl = new PHPTAL();
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
        $tpl = new PHPTAL();
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
        $tpl = new PHPTAL();
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
        $tpl = new PHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }
}

?>
