<?php

require_once 'config.php';
require_once 'PHPTAL.php';

class TalRepeatTest extends PHPUnit2_Framework_TestCase 
{
    function testArrayRepeat()
    {
        $tpl = new PHPTAL('input/tal-repeat.01.html');
        $tpl->array = range(0,4);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.01.html');
        $this->assertEquals($exp, $res);
    }

    function testOddEventAndFriends()
    {
        $tpl = new PHPTAL('input/tal-repeat.02.html');
        $tpl->array = range(0,2);
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-repeat.02.html');
        $this->assertEquals($exp, $res);        
    }

    function testIterableUsage()
    {
        $tpl = new PHPTAL('input/tal-repeat.03.html');
        $tpl->result = new MyIterable(4);
        $res = $tpl->execute();
        $res = trim_string($res);
        $exp = trim_file('output/tal-repeat.03.html');
        $this->assertEquals($exp, $res);        
    }
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

    public function size(){
        return $this->_size;
    }

    private $_index;
    private $_size;
}

?>
