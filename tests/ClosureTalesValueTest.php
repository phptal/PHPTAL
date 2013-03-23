<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Andrew Crites <explosion-pills@aysites.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.org/
 */

class ClosureTalesValueTest extends PHPTAL_TestCase
{
    function testClosureVariable()
    {
        if (strpos(phpversion(), '5.2') === 0) {
            $this->markTestSkipped();
        }
        $source = <<<HTML
<tal:block content="foon"/>
<tal:if condition="false">do not show</tal:if>
<tal:if condition="true">do show</tal:if>
<tal:each repeat="value array"><tal:block content="repeat/value/key"/>:<tal:block content="value"/></tal:each>
<tal:block content="use"/>
<tal:block define="varname inputvar" tal:content="varname"/>
<br class="omitme" tal:omit-tag="omitme"/>
<br class="keepme" tal:omit-tag="keepme"/>
<br tal:replace="structure replacetag"/>
<br tal:replace="keeptag"/>
<span tal:attributes="class classlist; data-empty nonvalue|string:; data-nothing nonextant|"/>
<tal:block on-error="errorhandler" content="nonextant"/>
HTML;
        $expected = <<<HTML
barn

do show
a:1b:2c:3
use
output

<br class="keepme"/>
<hr>

<span class="one two three" data-empty=""></span>
there was an error (but not really)
HTML;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);

        //eval is required for PHP 5.2 compatibility
        //anonymous functions/closures will cause a syntax error
        eval(<<<PHP
            \$tpl->foon = function () { return 'barn'; };
            \$false = function () { return false; };
            \$true = function () { return true; };
            \$tpl->false = \$false;
            \$tpl->true = \$true;
            \$tpl->array = function () { return array('a' => 1, 'b' => 2, 'c' => 3); };
            \$use = "use";
            \$tpl->use = function () use (\$use) { return \$use; };
            \$tpl->inputvar = function () { return "output"; };
            \$tpl->omitme = \$true;
            \$tpl->keepme = \$false;
            \$tpl->replacetag = function () { return "<hr>"; };
            \$tpl->keeptag = function () { return ''; };
            \$tpl->classlist = function () { return 'one two three'; };
            \$tpl->nonvalue = function () { return; };
            \$tpl->errorhandler = function () {
                return 'there was an error (but not really)';
            };
PHP
        );
        $this->assertEquals($expected, $tpl->execute());
    }
}
?>
