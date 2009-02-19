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

/**
 * Stores tal:repeat information during template execution.
 *
 * An instance of this class is created and stored into PHPTAL context on each
 * tal:repeat usage.
 *
 * repeat/item/index
 * repeat/item/number
 * ...
 * are provided by this instance.
 *
 * 'repeat' is an StdClass instance created to handle RepeatControllers, 
 * 'item' is an instance of this class. 
 * 
 * @package phptal
 * @author Laurent Bedubourg <lbedubourg@motion-twin.com>
 */
class PHPTAL_RepeatController implements Iterator
{
    private $key;
    private $current;
    private $valid;
    private $validOnNext;

    protected $iterator;
    public $index;
    public $end;

    /**
     * Construct a new RepeatController.
     *
     * @param $source array, string, iterator, iterable.
     */
    public function __construct($source)
    {
        if ( is_string($source) ) {
            $this->iterator = new ArrayIterator( str_split($source) );  // FIXME: invalid for UTF-8 encoding, use preg_match_all('/./u') trick
        } else if ( is_array($source) ) {
            $this->iterator = new ArrayIterator($source);
        } else if ( $source instanceof IteratorAggregate ) {
            $this->iterator = $source->getIterator();
        } else if ( $source instanceof Iterator ) {        
            $this->iterator = $source;
        } else if ( $source instanceof SimpleXMLElement) { // has non-unique keys!
            $array = array();
            foreach ( $source as $v ) {
                $array[] = $v;
            }
            $this->iterator = new ArrayIterator($array);
        } else if ( $source instanceof Traversable || $source instanceof DOMNodeList ) {
            // PDO Statements implement an internal Traversable interface. 
            // To make it fully iterable we traverse the set to populate
            // an array which will be actually used for iteration.
            $array = array();
            foreach ( $source as $k=>$v ) {
                $array[$k] = $v;
            }
            $this->iterator = new ArrayIterator($array);
        } else if ( $source instanceof stdClass ) {
            $this->iterator = new ArrayIterator( (array) $source );
        } else {
            $this->iterator = new ArrayIterator( array() );
        }
    
        $this->groups = new PHPTAL_RepeatController_Groups();
    }
  
    /**
     * Returns the current element value in the iteration
     *
     * @return Mixed    The current element value
     */
    public function current()
    {
        return $this->current;
    }
    
    /**
     * Returns the current element key in the iteration
     *
     * @return String/Int   The current element key
     */
    public function key()
    {
        return $this->key;
    }
    
    /**
     * Tells if the iteration is over
     *
     * @return bool     True if the iteration is not finished yet
     */
    public function valid()
    {   
        $valid = $this->valid || $this->validOnNext;
        $this->validOnNext = $this->valid;
        
        return $valid;
    }

    private $length = NULL;
    public function length()
    {
        if ($this->length === NULL)
        {
            if ( $this->iterator instanceof Countable ) 
            {
                return $this->length = count($this->iterator);
            }
            else if ( is_object($this->iterator) ) 
            {
                // for backwards compatibility with existing PHPTAL templates
                if ( method_exists( $this->iterator, 'size' ) ) 
                {
                    return $this->length = $this->iterator->size();                
                } 
                else if ( method_exists( $this->iterator, 'length' ) ) 
                {
                    return $this->length = $this->iterator->length();
                }
            }            
            $this->length = '_PHPTAL_LENGTH_UNKNOWN_';
        }
        
        if ($this->length === '_PHPTAL_LENGTH_UNKNOWN_') // return length if end is discovered
        {
            return $this->end ? $this->index + 1 : NULL;
        }
        return $this->length;
    }

    /**
     * Restarts the iteration process going back to the first element
     *
     */
    public function rewind()
    {
        $this->index = 0;
        $this->length = NULL;
        $this->end = false;
        
        $this->iterator->rewind();

        // Prefetch the next element
        if ( $this->iterator->valid() ) {
            $this->validOnNext = true;
            $this->prefetch();
        } else {
            $this->validOnNext = false;
        }
        
        // Notify the grouping helper of the change
        $this->groups->reset();        
    }

    /**
     * Fetches the next element in the iteration and advances the pointer
     *
     */
    public function next()
    {
        $this->index++;        
        
        // Prefetch the next element
        if ($this->validOnNext) $this->prefetch();
        
        // Notify the grouping helper of the change
        $this->groups->reset();        
    }
    
    /**
     * Gets an object property
     *
     * @return $var  Mixed  The variable value
     */
    public function __get( $var )
    {
        switch ( $var ) {
            case 'number':
                return $this->index + 1;
            case 'start':
                return $this->index === 0;
            case 'even':
                return ($this->index % 2) === 0;
            case 'odd':
                return ($this->index % 2) === 1;
            case 'key':
                return $this->key;
            case 'length':
                return $this->length();
            case 'letter':
                return strtolower( $this->int2letter($this->index+1) );
            case 'Letter':
                return strtoupper( $this->int2letter($this->index+1) );            
            case 'roman':
                return strtolower( $this->int2roman($this->index+1) );
            case 'Roman':            
                return strtoupper( $this->int2roman($this->index+1) );
                
            case 'first':
                // Compare the current one with the previous in the dictionary
                $res = $this->groups->first( $this->current );                    
                return is_bool($res) ? $res : $this->groups;
            case 'last':
                // Compare the next one with the dictionary
                $res = $this->groups->last( $this->iterator->current() );
                return is_bool($res) ? $res : $this->groups;
            
            default:
                throw new PHPTAL_VariableNotFoundException( "Unable to find part '$var' in repeat variable" );
        }
    }    
    
    /**
     * Fetches the next element from the source data store and
     * updates the end flag if needed. 
     *
     * @access protected
     */
    protected function prefetch()
    {
        $this->valid = true;
        $this->current = $this->iterator->current();
        $this->key = $this->iterator->key();

        $this->iterator->next();
        if ( !$this->iterator->valid() ) {
            $this->valid = false;
            $this->end = true;
        }
    }    
    
    /**
     * Converts an integer number (1 based) to a sequence of letters
     *     
     * @param $int Int  The number to convert
     * @return String   The letters equivalent as a, b, c-z ... aa, ab, ac-zz ...
     * @access protected
     */
    protected function int2letter( $int )
    {
        $lookup = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $size = strlen($lookup);

        $letters = '';
        while ( $int > 0 ) {
            $int--;
            $letters = $lookup[$int % $size] . $letters;
            $int = floor($int / $size);
        }
        return $letters;
    }
    
    /**
     * Converts an integer number (1 based) to a roman numeral
     *     
     * @param $int Int  The number to convert
     * @return String   The roman numeral
     * @access protected
     */
    protected function int2roman( $int )
    {
        $lookup = array(
            '1000'  => 'M',
            '900'   => 'CM',
            '500'   => 'D',
            '400'   => 'CD',
            '100'   => 'C',
            '90'    => 'XC',
            '50'    => 'L',
            '40'    => 'XL',
            '10'    => 'X',
            '9'     => 'IX',
            '5'     => 'V',
            '4'     => 'IV',
            '1'     => 'I',
        );
        
        $roman = '';
        foreach ( $lookup as $max => $letters ) {
            while ( $int >= $max ) {
                $roman .= $letters;
                $int -= $max;
            }
        }
        
        return $roman;
    }
}


/**
 * Keeps track of variable contents when using grouping in a path (first/ and last/)
 *
 * @package phptal
 * @author Iván Montes <drslump@pollinimini.net>
 */
class PHPTAL_RepeatController_Groups {

    protected $dict = array();
    protected $cache = array();
    protected $data = null;
    protected $vars = array();
    protected $branch;
    
        
    public function __construct()
    {
        $this->dict = array();        
        $this->reset();
    }
    
    /**
     * Resets the result caches. Use it to signal an iteration in the loop
     * 
     */
    public function reset()
    {
        $this->cache = array();
    }
        
    /**
     * Checks if the data passed is the first one in a group
     * 
     * @param $data Mixed   The data to evaluate
     * @return Mixed    True if the first item in the group, false if not and
     *                  this same object if the path is not finished
     */ 
    public function first( $data )
    {
        if ( !is_array($data) && !is_object($data) && !is_null($data) ) {
            
            if ( !isset($this->cache['F']) ) {
                
                $hash = md5($data);
                
                if ( !isset($this->dict['F']) || $this->dict['F'] !== $hash ) {                
                    $this->dict['F'] = $hash;
                    $res = true;
                } else {
                    $res = false;
                }
                
                $this->cache['F'] = $res;
            }
            
            return $this->cache['F'];
        }
        
        $this->data = $data;
        $this->branch = 'F';
        $this->vars = array();
        return $this;
    }
   
    /**
     * Checks if the data passed is the last one in a group
     * 
     * @param $data Mixed   The data to evaluate
     * @return Mixed    True if the last item in the group, false if not and
     *                  this same object if the path is not finished
     */ 
    public function last( $data )
    {
        if ( !is_array($data) && !is_object($data) && !is_null($data) ) {
            
            if ( !isset($this->cache['L']) ) {
                
                $hash = md5($data);
                
                if (empty($this->dict['L'])) {
                    $this->dict['L'] = $hash;
                    $res = false;
                } else if ( $this->dict['L'] !== $hash ) {
                    $this->dict['L'] = $hash;
                    $res = true;
                } else {
                    $res = false;
                }
                
                $this->cache['L'] = $res;
            }
            
            return $this->cache['L'];
        }
        
        $this->data = $data;
        $this->branch = 'L';
        $this->vars = array();
        return $this;
    }    
    
    /**
     * Handles variable accesses for the tal path resolver
     *
     * @param $var String   The variable name to check
     * @return Mixed    An object/array if the path is not over or a boolean
     *
     * @todo    replace the phptal_path() with custom code
     */
    public function __get( $var )
    {
        // When the iterator item is empty we just let the tal 
        // expression consume by continuously returning this 
        // same object which should evaluate to true for 'last'
        if ( is_null($this->data) ) {
            return $this;
        }
        
        // Find the requested variable
        $value = @phptal_path( $this->data, $var, true );
        
        // Check if it's an object or an array
        if ( is_array($value) || is_object($value) ) {
            // Move the context to the requested variable and return
            $this->data = $value;
            $this->addVarName( $var );
            return $this;
        }
        
        // get a hash of the variable contents
        $hash = md5( $value );
        
        // compute a path for the variable to use as dictionary key
        $path = $this->branch . $this->getVarPath() . $var;

        // If we don't know about this var store in the dictionary        
        if ( !isset($this->cache[$path]) ) {
        
            if ( !isset($this->dict[$path]) ) {
                $this->dict[$path] = $hash;
                $res = $this->branch === 'F';
            } else {
                // Check if the value has changed
                if ( $this->dict[$path] !== $hash ) {
                    $this->dict[$path] = $hash;
                    $res = true;
                } else {
                    $res = false;
                }
            }
            
            $this->cache[$path] = $res;
        }
        
        return $this->cache[$path];

    }

    /**
     * Adds a variable name to the current path of variables
     *
     * @param $varname String  The variable name to store as a path part
     * @access protected
     */
    protected function addVarName( $varname )
    {
        $this->vars[] = $varname;
    }

    /**
     * Returns the current variable path separated by a slash
     *
     * @return String  The current variable path
     * @access protected
     */
    protected function getVarPath()
    {
        return implode('/', $this->vars) . '/';
    }
}
