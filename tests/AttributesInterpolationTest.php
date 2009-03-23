<?php

class AttributesInterpolationTest extends PHPUnit_Framework_Testcase
{
    public function testInterpol()
    {
        $src = <<<EOT
<span title="\${foo}"></span>
EOT;
        $exp = <<<EOT
<span title="foo value"></span>
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
<span title="\${foo2} x \${structure foo} y \${foo}\${structure foo2}"></span><img/>
EOT;
        $exp = <<<EOT
<span title="{foo2 &lt;img /&gt;} x foo value y foo value{foo2 <img />}"></span><img/>
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
<span title="\${foo}\${foo}1"></span>
<span tal:attributes="title string:\${foo}\${foo}2"></span>
<span tal:attributes="title '\${foo}\${foo}3'"></span>
EOT;
        $exp = <<<EOT
<span title="foo valuefoo value1"></span>
<span title="foo valuefoo value2"></span>
<span title="foo valuefoo value3"></span>
EOT;
        $tpl = new PHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }    
    
    public function testInterpol3a()
    {
        $src = <<<EOT
<span tal:attributes="title php:'\${foo}\${foo}'"></span>
<span title="<?php echo '\${foo}\${foo}' ?>"></span>
EOT;
    $exp = <<<EOT
<span title="\${foo}\${foo}"></span>
<span title="\${foo}\${foo}"></span>
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
<span title="$\${foo}"></span>
EOT;
        $exp = <<<EOT
<span title="\${foo}"></span>
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
<span title="$$\${foo}"></span>
EOT;
        $exp = <<<EOT
<span title="$\${foo}"></span>
EOT;
        $tpl = new PHPTAL();
        $tpl->setSource($src);
        $tpl->foo = 'foo value';
        $res = $tpl->execute();
        $this->assertEquals($exp, $res);
    }
}

?>
