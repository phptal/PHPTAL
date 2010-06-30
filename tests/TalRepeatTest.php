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


class TalRepeatTest extends PHPTAL_TestCase
{
    function testArrayRepeat()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.01.html');
        $tpl->array = range(0, 4);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-repeat.01.html');
        $this->assertEquals($exp, $res);
    }

    function testOddEventAndFriends()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.02.html');
        $tpl->array = range(0, 2);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-repeat.02.html');
        $this->assertEquals($exp, $res);
    }

    function testIterableUsage()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.03.html');
        $tpl->result = new MyIterableWithSize(4);
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-repeat.03.html');
        $this->assertEquals($exp, $res);
    }

    function testArrayObject()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><p tal:repeat="a aobj" tal:content="a"></p><p tal:repeat="a aobj" tal:content="a"></p></div>');
        $tpl->aobj = new MyArrayObj(array(1, 2, 3));

        $this->assertEquals('<div><p>1</p><p>2</p><p>3</p><p>1</p><p>2</p><p>3</p></div>', $tpl->execute());
    }

    function testArrayObjectOneElement()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><p tal:repeat="a aobj" tal:content="a"></p><p tal:repeat="a aobj" tal:content="a"></p></div>');
        $tpl->aobj = new MyArrayObj(array(1));

        $this->assertEquals('<div><p>1</p><p>1</p></div>', $tpl->execute());
    }

    function testArrayObjectZeroElements()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><p tal:repeat="a aobj" tal:content="a"></p><p tal:repeat="a aobj" tal:content="a"/></div>');
        $tpl->aobj = new MyArrayObj(array());

        $this->assertEquals('<div></div>', $tpl->execute());
    }

    function testArrayObjectAggregated()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><p tal:repeat="a aobj">${a}${repeat/a/length}</p></div>');
        $tpl->aobj = new MyArrayObj(new MyArrayObj(array("1", "2", "3", null)));

        $this->assertEquals('<div><p>14</p><p>24</p><p>34</p><p>4</p></div>', $tpl->execute());
    }

    function testArrayObjectNested()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('<div><p tal:repeat="a aobj">${a}<b tal:repeat="b aobj" tal:content="b"/></p></div>');
        $tpl->aobj = new MyArrayObj(array("1", "2"));

        $this->assertEquals('<div><p>1<b>1</b><b>2</b></p><p>2<b>1</b><b>2</b></p></div>', $tpl->execute());
    }

    function testHashKey()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.04.html');
        $tpl->result = array('a'=>0, 'b'=>1, 'c'=>2, 'd'=>3);
        $res = $tpl->execute();
        $res = normalize_html($res);
        $exp = normalize_html_file('output/tal-repeat.04.html');
        $this->assertEquals($exp, $res);
    }

    function testRepeatAttributesWithPhp()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.05.html');
        $tpl->data = array(1, 2, 3);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-repeat.05.html');
        $this->assertEquals($exp, $res);
    }


    function testRepeatAttributesWithMacroPhp()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.06.html');
        $tpl->data = array(1, 2, 3);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-repeat.06.html');
        $this->assertEquals($exp, $res);
    }


    function testPhpMode()
    {
        $tpl = $this->newPHPTAL('input/tal-repeat.07.html');
        $tpl->result = array('a'=>0, 'b'=>1, 'c'=>2, 'd'=>3);
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/tal-repeat.07.html');
        $this->assertEquals($exp, $res);
    }

    function testInterpolatedPHP()
    {
        $tpl = $this->newPHPTAL();
        $tpl->y = 'somearray';
        $tpl->somearray = array(1=>9, 9, 9);
        $tpl->setSource('<div tal:repeat="x php:${y}">${repeat/x/key}</div>');
        $this->assertEquals('<div>1</div><div>2</div><div>3</div>', $tpl->execute());
    }

    function testTraversableRepeat()
    {
        $doc = new DOMDocument();
        $doc->loadXML('<a><b/><c/><d/><e/><f/><g/></a>');

        $tpl = $this->newPHPTAL();
        $tpl->setSource('<tal:block tal:repeat="node nodes"><tal:block tal:condition="php:repeat.node.index==4">(len=${repeat/node/length})</tal:block>${repeat/node/key}${node/tagName}</tal:block>');
        $tpl->nodes = $doc->getElementsByTagName('*');

        $this->assertEquals('0a1b2c3d(len=7)4e5f6g', $tpl->execute());

    }

    function testLetter()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource( '<span tal:omit-tag="" tal:repeat="item items" tal:content="repeat/item/letter"/>' );
        $tpl->items = range( 0, 32 );
        $res = normalize_html( $tpl->execute() );
        $exp = 'abcdefghijklmnopqrstuvwxyzaaabacadaeafag';
        $this->assertEquals( $exp, $res );
    }

    function testRoman()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource( '<span tal:omit-tag="" tal:repeat="item items" tal:content="string:${repeat/item/roman},"/>' );
        $tpl->items = range( 0, 16 );
        $res = normalize_html( $tpl->execute() );
        $exp = 'i,ii,iii,iv,v,vi,vii,viii,ix,x,xi,xii,xiii,xiv,xv,xvi,xvii,';
        $this->assertEquals( $exp, $res );
    }

    function testGrouping()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('
            <div tal:omit-tag="" tal:repeat="item items">
                <h1 tal:condition="repeat/item/first" tal:content="item"></h1>
                <p tal:condition="not: repeat/item/first" tal:content="item"></p>
                <hr tal:condition="repeat/item/last" />
            </div>'
        );
        $tpl->items = array( 'apple', 'apple', 'orange', 'orange', 'orange', 'pear', 'kiwi', 'kiwi' );
        $res = normalize_html( $tpl->execute() );
        $exp = normalize_html('
            <h1>apple</h1>
            <p>apple</p>
            <hr/>
            <h1>orange</h1>
            <p>orange</p>
            <p>orange</p>
            <hr/>
            <h1>pear</h1>
            <hr/>
            <h1>kiwi</h1>
            <p>kiwi</p>
            <hr/>'
        );

        $this->assertEquals( $exp, $res );
    }

    function testGroupingPath()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource('
            <div tal:omit-tag="" tal:repeat="item items">
                <h1 tal:condition="repeat/item/first/type" tal:content="item/type"></h1>
                <p tal:content="item/name"></p>
                <hr tal:condition="repeat/item/last/type" />
            </div>'
        );
        $tpl->items = array(
                            array( 'type' => 'car', 'name' => 'bmw' ),
                            array( 'type' => 'car', 'name' => 'audi' ),
                            array( 'type' => 'plane', 'name' => 'boeing' ),
                            array( 'type' => 'bike', 'name' => 'suzuki' ),
                            array( 'type' => 'bike', 'name' => 'honda' ),
        );
        $res = normalize_html( $tpl->execute() );
        $exp = normalize_html('
            <h1>car</h1>
            <p>bmw</p>
            <p>audi</p>
            <hr/>
            <h1>plane</h1>
            <p>boeing</p>
            <hr/>
            <h1>bike</h1>
            <p>suzuki</p>
            <p>honda</p>
            <hr/>'
        );

        $this->assertEquals( $exp, $res );
    }

    function testSimpleXML()
    {
        $tpl = $this->newPHPTAL();
        $tpl->setSource("<tal:block tal:repeat='s php:sxml'><b tal:content='structure s' />\n</tal:block>");
        $tpl->sxml = new SimpleXMLElement("<x><y>test</y><y attr=\"test\"><z>test</z></y><y/></x>");
        $this->assertEquals("<b><y>test</y></b>\n<b><y attr=\"test\"><z>test</z></y></b>\n<b><y/></b>\n", $tpl->execute());
    }


    function testSameCallsAsForeach()
    {
        $foreach = new LogIteratorCalls(array(1, 2, 3));

        foreach ($foreach as $k => $x) {
        }

        $controller = new LogIteratorCalls(array(1, 2, 3));

        $phptal = $this->newPHPTAL();
        $phptal->iter = $controller;
        $phptal->setSource('<tal:block tal:repeat="x iter" />');
        $phptal->execute();

        $this->assertEquals($foreach->log, $controller->log);
    }

    function testCountIsLazy()
    {
        $tpl = $this->newPHPTAL();
        $tpl->i = new MyIterableThrowsOnSize(10);
        $tpl->setSource('<tal:block tal:repeat="i i">${repeat/i/start}[${repeat/i/key}]${repeat/i/end}</tal:block>');
        $this->assertEquals("1[0]00[1]00[2]00[3]00[4]00[5]00[6]00[7]00[8]00[9]1", $tpl->execute());

        try
        {
            $tpl->i = new MyIterableThrowsOnSize(10);
            $tpl->setSource('<tal:block tal:repeat="i i">aaaaa${repeat/i/length}aaaaa</tal:block>');
            echo $tpl->execute();
            $this->fail("Expected SizeCalledException");
        }
        catch(SizeCalledException $e) {}
    }

    function testReset()
    {
        $tpl = $this->newPHPTAL();
        $tpl->iter = $i = new LogIteratorCalls(new MyIterableThrowsOnSize(10));

        $tpl->setSource('<tal:block tal:repeat="i iter">${repeat/i/start}[${repeat/i/key}]${repeat/i/end}</tal:block><tal:block tal:repeat="i iter">${repeat/i/start}[${repeat/i/key}]${repeat/i/end}</tal:block>');

        $res = $tpl->execute();
        $this->assertEquals("1[0]00[1]00[2]00[3]00[4]00[5]00[6]00[7]00[8]00[9]11[0]00[1]00[2]00[3]00[4]00[5]00[6]00[7]00[8]00[9]1", $res,$tpl->getCodePath());
        $this->assertRegExp("/rewind.*rewind/s",$i->log);
        $this->assertEquals("1[0]00[1]00[2]00[3]00[4]00[5]00[6]00[7]00[8]00[9]11[0]00[1]00[2]00[3]00[4]00[5]00[6]00[7]00[8]00[9]1", $tpl->execute());
    }

    function testFakedLength()
    {
        $tpl = $this->newPHPTAL();
        $tpl->iter = new MyIterable(10);
        $tpl->setSource('<tal:block tal:repeat="i iter">${repeat/i/start}[${repeat/i/key}/${repeat/i/length}]${repeat/i/end}</tal:block>');
        $this->assertEquals("1[0/]00[1/]00[2/]00[3/]00[4/]00[5/]00[6/]00[7/]00[8/]00[9/10]1", $tpl->execute(), $tpl->getCodePath());
    }

    function testPushesContext()
    {
        $phptal = $this->newPHPTAL();
        $phptal->setSource('
        <x>
        original=${user}
        <y tal:define="user \'defined\'">
        defined=${user}
        <z tal:repeat="user users">
        repeat=${user}
        <z tal:repeat="user users2">
        repeat2=${user}
        </z>
        repeat=${user}
        </z>
        defined=${user}
        </y>
        original=${user}</x>
        ');

        $phptal->user = 'original';
        $phptal->users = array('repeat');
        $phptal->users2 = array('repeat2');

        $this->assertEquals(
            normalize_html('<x> original=original <y> defined=defined <z> repeat=repeat <z> repeat2=repeat2 </z> repeat=repeat </z> defined=defined </y> original=original</x>'),
            normalize_html($phptal->execute()));
    }
}

class LogIteratorCalls implements Iterator
{
    public $i, $log = '';
    function __construct($arr)
    {
        if ($arr instanceof Iterator) $this->i = $arr; else $this->i = new ArrayIterator($arr);
    }

    function current()
    {
        $this->log .= "current\n";
        return $this->i->current();
    }
    function next()
    {
        $this->log .= "next\n";
        return $this->i->next();
    }
    function key()
    {
        $this->log .= "key\n";
        return $this->i->key();
    }
    function rewind()
    {
        $this->log .= "rewind\n";
        return $this->i->rewind();
    }
    function valid()
    {
        $this->log .= "valid\n";
        return $this->i->valid();
    }
}

class MyArrayObj extends ArrayObject
{

}

class MyIterable implements Iterator
{
    public function __construct($size){
        $this->_index = 0;
        $this->_size= $size;
    }

    public function rewind(){
        $this->_index = 0;
    }

    public function current(){
        return $this->_index;
    }

    public function key(){
        return $this->_index;
    }

    public function next(){
        $this->_index++;
        return $this->_index;
    }

    public function valid(){
        return $this->_index < $this->_size;
    }

    private $_index;
    protected $_size;
}

class MyIterableWithSize extends MyIterable
{
    public function size(){
        return $this->_size;
    }
}

class SizeCalledException extends Exception {}

class MyIterableThrowsOnSize extends MyIterable implements Countable
{
    public function count()
    {
        throw new SizeCalledException("count() called");
    }

    public function length()
    {
        throw new SizeCalledException("length() called");
    }

    public function size()
    {
        throw new SizeCalledException("size() called");
    }
}

