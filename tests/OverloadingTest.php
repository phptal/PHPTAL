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

class OverloadTestClass
{
    public $vars = array('foo'=>'bar', 'baz'=>'biz');

    public function __set( $name, $value )
    {
        $this->vars[$name] = $value;
    }

    public function __get( $name )
    {
        if (array_key_exists($name, $this->vars)) {
            return $this->vars[$name];
        }
        return null;
    }

    public function __isset( $key )
    {
        return isset($this->$key) || array_key_exists($key, $this->vars);
    }

    public function __call( $func, $args )
    {
        return "$func()=".join(',', $args);
    }
}



class OverloadingTest extends PHPTAL_TestCase
{
    function test()
    {
        $tpl = $this->newPHPTAL('input/overloading-01.html');
        $tpl->object = new OverloadTestClass();
        $res = normalize_html($tpl->execute());
        $exp = normalize_html_file('output/overloading-01.html');
        $this->assertEquals($exp, $res);
    }
}

