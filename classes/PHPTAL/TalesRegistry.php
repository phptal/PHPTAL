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
//  Authors: Moritz Bechler <mbechler@eenterphace.org>
//

require_once PHPTAL_DIR.'PHPTAL/Tales.php';

/**
 * Global registry of TALES expression modifiers
 *
 */
class PHPTAL_TalesRegistry {

	static $instance;

	static public function initialize() {
		self::$instance = new PHPTAL_TalesRegistry();
	}

	/**
	 * Enter description here...
	 *
	 * @return PHPTAL_TalesRegistry
	 */
	static public function getInstance() {
		if(!(self::$instance instanceof PHPTAL_TalesRegistry)) {
			self::initialize();
		}

		return self::$instance;
	}

	protected function __construct() {

	}

	/**
	 *
	 * Expects an either a function name or an array of class and method as
	 * callback.
	 *
	 * @param unknown_type $prefix
	 * @param unknown_type $callback
	 */
	public function registerPrefix($prefix, $callback) {
		if($this->isRegistered($prefix)) {
			throw new PHPTAL_ConfigurationException(sprintf('Expression modifier "%s" is already registered.',$prefix));
		}

		// Check if valid callback

		if(is_array($callback)) {

			$class = new ReflectionClass($callback[0]);

			if(!$class->isSubclassOf('PHPTAL_Tales')) {
				throw new PHPTAL_ConfigurationException('The class you want to register does not implement "PHPTAL_Tales".');
			}

			$method = new ReflectionMethod($callback[0], $callback[1]);

			if(!$method->isStatic()) {
				throw new PHPTAL_ConfigurationException('The method you want to register is not static.');
			}

			// maybe we want to check the parameters the method takes

		} else {
			if(!function_exists($callback)) {
				throw new PHPTAL_ConfigurationException('The function you are trying to register does not exist.');
			}
		}


		$this->_callbacks[$prefix] = $callback;
	}

	public function isRegistered($prefix) {
		return (array_key_exists($prefix, $this->_callbacks));
	}

	public function getCallback($prefix) {
		if(!$this->isRegistered($prefix)) {
			throw new PHPTAL_ConfigurationException(sprintf('Expression modifier "%s" is not registered.', $prefix));
		}
		return $this->_callbacks[$prefix];
	}

	private $_callbacks = array();
}

