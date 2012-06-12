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

class OldTest extends PHPTAL_TestCase
{
    function test03()
    {
        $tpl = $this->newPHPTAL('input/old-03.html');
        $tpl->title = 'My dynamic title';
        $tpl->content = '<p>my content</p>';
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-03.html');
        $this->assertEquals($exp, $res);
    }

    function test06()
    {
        $tpl = $this->newPHPTAL('input/old-06.html');
        $tpl->title = 'my title';
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-06.html');
        $this->assertEquals($exp, $res);
    }

    function test08()
    {
        $tpl = $this->newPHPTAL('input/old-08.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-08.html');
        $this->assertEquals($exp, $res);
    }

    function test11()
    {
        $tpl = $this->newPHPTAL('input/old-11.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-11.html');
        $this->assertEquals($exp, $res);
    }

    function test12()
    {
        $tpl = $this->newPHPTAL('input/old-12.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-12.html');
        $this->assertEquals($exp, $res);
    }

    function test13()  // default keyword
    {
        $tpl = $this->newPHPTAL('input/old-13.html');
        $l = new stdClass(); // DummyTag();
        $l->href= "http://www.example.com";
        $l->title = "example title";
        $l->name = "my link content";
        $tpl->a2 = "a value";
        $tpl->link2 = $l;

        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-13.html');
        $this->assertEquals($exp, $res);
    }

    function test16() // default in attributes
    {
        $tpl = $this->newPHPTAL('input/old-16.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-16.html');
        $this->assertEquals($exp, $res);
    }

    function test17() // test indents
    {
        $tpl = $this->newPHPTAL('input/old-17.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-17.html');
        $this->assertEquals($exp, $res);
    }


    function test19() // attribute override
    {
        $tpl = $this->newPHPTAL('input/old-19.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-19.html');
        $this->assertEquals($exp, $res);
    }


    function test20() // remove xmlns:tal, xmlns:phptal, xmlns:metal, xmlns:i18n
    {
        $tpl = $this->newPHPTAL('input/old-20.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-20.html');
        $this->assertEquals($exp, $res);
    }


    function test21() // ensure xhtml reduced tags are reduced
    {
        $tpl = $this->newPHPTAL('input/old-21.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-21.html');
        $this->assertEquals($res, $exp);
    }


    function test23() // test custom modifier
    {
        $tpl = $this->newPHPTAL('input/old-23.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-23.html');
        $this->assertEquals($res, $exp);
    }


    function test29() // test doctype inherited from macro
    {
        $tpl = $this->newPHPTAL('input/old-29.html');
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-29.html');
        $this->assertEquals($exp, $res);
    }

    function test30() // test blocks
    {
        $tpl = $this->newPHPTAL('input/old-30.html');
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-30.html');
        $this->assertEquals($exp, $res);
    }

    function test31() // test path evals
    {
        $a = new stdClass;
        $a->fooval = new stdClass;
        $a->fooval->b = new stdClass;
        $a->fooval->b->barval = "it's working";

        $tpl = $this->newPHPTAL('input/old-31.html');
        $tpl->a = $a;
        $tpl->foo = 'fooval';
        $tpl->bar = 'barval';
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/old-31.html');
        $this->assertEquals($exp, $res);
    }

    function test32() // recursion
    {
        $o = array(
            'title' => 'my object',
            'children' => array(
                array('title' => 'o.1', 'children'=>array(
                    array('title'=>'o.1.1', 'children'=>array()),
                    array('title'=>'o.1.2', 'children'=>array()),
                      )),
                array('title' => 'o.2', 'children'=>array()),
            )
        );

        $tpl = $this->newPHPTAL('input/old-32.html');
        $tpl->object = $o;
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/old-32.html');
        $this->assertEquals($exp, $res);
    }
}


function phptal_tales_my_modifier( $arg, $nothrow )
{
    return "strtoupper('$arg')";
}

