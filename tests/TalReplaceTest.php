<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//  
//  Copyright (c) 2004-2005 Laurent Bedubourg
//  
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//  
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//  
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//  
//  Authors: Laurent Bedubourg <lbedubourg@motion-twin.com>
//  

require_once 'config.php';
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

class TalReplaceTest extends PHPUnit_Framework_TestCase 
{
    function testSimple()
    {
        $tpl = new PHPTAL('input/tal-replace.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.01.html');
        $this->assertEquals($exp, $res);
    }

    function testVar()
    {
        $tpl = new PHPTAL('input/tal-replace.02.html');
        $tpl->replace = 'my replace';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $tpl = new PHPTAL('input/tal-replace.03.html');
        $tpl->replace = '<foo><bar/></foo>';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.03.html');
        $this->assertEquals($exp, $res);
    }

    function testNothing()
    {
        $tpl = new PHPTAL('input/tal-replace.04.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.04.html');
        $this->assertEquals($exp, $res);
    }

    function testDefault()
    {
        $tpl = new PHPTAL('input/tal-replace.05.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.05.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = new PHPTAL('input/tal-replace.06.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.06.html');
        $this->assertEquals($exp, $res);
    }

    function testBlock()
    {
        $tpl = new PHPTAL('input/tal-replace.07.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-replace.07.html');
        $this->assertEquals($exp, $res);        
    }

    function testEmpty()
    {
        $src = <<<EOT
<root>
<span tal:replace="nullv | falsev | emptystrv | zerov | default">default</span>
<span tal:replace="nullv | falsev | emptystrv | default">default</span>
</root>
EOT;
        $exp = <<<EOT
<root>
0
<span>default</span>
</root>
EOT;
        $tpl = new PHPTAL();
        $tpl->setSource($src, __FILE__);
        $tpl->nullv = null;
        $tpl->falsev = false;
        $tpl->emptystrv = '';
        $tpl->zerov = 0;
        $res = $tpl->execute();
        $this->assertEquals(trim_string($exp), trim_string($res));
    }
}

?>
