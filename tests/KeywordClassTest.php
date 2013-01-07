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


class KeywordClassTest extends PHPTAL_TestCase
{
    function testOnlyKeywords()
    {
        $source = <<<HTML
<tal:block content="">nothing</tal:block>
<tal:block content="nothing">nothing</tal:block>
<tal:block content="default">default</tal:block>
<tal:block content="nonextant|"></tal:block>
<tal:block content="nonextant|nothing"></tal:block>
<tal:block content="nonextant|default">default</tal:block>
<tal:block condition="">false</tal:block>
<tal:block condition="nothing">false</tal:block>
<tal:block condition="default">true</tal:block>
<tal:block repeat="nothing">repeat</tal:block>
<tal:block repeat="default">repeat</tal:block>
HTML;
        $expected = <<<HTML


default


default


true


HTML;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($source);
        $this->assertEquals($expected, $tpl->execute());
    }

    function testKeywordsWithModifiers()
    {
        $source = <<<HTML
<tal:block content="true:">nothing</tal:block>
<tal:block content="not:">nothing</tal:block>
<tal:block content="empty:">nothing</tal:block>
<tal:block content="json:">nothing</tal:block>
<tal:block content="true:nothing">nothing</tal:block>
<tal:block content="not:nothing">nothing</tal:block>
<tal:block content="empty:nothing">nothing</tal:block>
<tal:block content="json:nothing">nothing</tal:block>
<tal:block content="true:default">default</tal:block>
<tal:block content="not:default">default</tal:block>
<tal:block content="empty:default">default</tal:block>
<tal:block content="json:default">default</tal:block>
<tal:block condition="true:">false</tal:block>
<tal:block condition="not:">true</tal:block>
<tal:block condition="empty:">true</tal:block>
<tal:block condition="true:nothing">false</tal:block>
<tal:block condition="not:nothing">true</tal:block>
<tal:block condition="empty:nothing">true</tal:block>
<tal:block condition="true:default">true</tal:block>
<tal:block condition="not:default">false</tal:block>
<tal:block condition="empty:default">false</tal:block>
HTML;
        $expected = <<<HTML

nothing
nothing
null

nothing
nothing
null

default


{}

true
true

true
true
true


HTML;
    }
}
?>
