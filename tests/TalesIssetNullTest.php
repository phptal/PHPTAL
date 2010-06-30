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

class DummyObjectX
{
    public function __contruct()
    {
        $this->_data = array();
    }
    public function __isset($var)
    {
        return array_key_exists($var, $this->_data);
    }
    public function __get($var)
    {
        return $this->_data[$var];
    }
    public function __set($var, $value)
    {
        $this->_data[$var] = $value;
    }
    public function __call($method, $params)
    {
        return '__call';
    }
    private $_data;
}



class TalesIssetNullTest extends PHPTAL_TestCase
{
    function testIt()
    {
        $dummy = new DummyObjectX();
        $dummy->foo = null;

        $res = PHPTAL_Context::path($dummy, 'method');
        $this->assertEquals('__call', $res);

        $res = phptal_path($dummy, 'foo');
        $this->assertEquals(null, $res);
    }
}

