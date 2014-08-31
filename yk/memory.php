<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  memory.php 2014-5-20
 */

namespace yk;

class memory{
	
	private static $_instance;
	
	/**
	 * @var \ext\memory\base
	 */
	private $_memory;
	private $_extension = [];
	
	public static function getInstance(){
		if (!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	private function __construct(){
		foreach ($this->_extension as $memory){
			if (!is_object($memory)) {
				$class = "\yk\\memory\\$memory";
				$memory = new $class;
			}
			if ($memory->isEnable()) {
				$this->_memory = $memory;
				break;
			}
		}
	}
	
	public function getCache(){
		return $this->_memory;
	}
	
	public function addMemory($memory){
		$this->_extension[] = $memory;
	}
	
	public function set($key, $value, $ttl){
		return $this->_memory->set($key, $value, $ttl);
	}
	
	public function get($key){
		return $this->_memory->get($key);
	}
	
	public function del($key){
		return $this->_memory->del($key);
	}
	
	public function exist($key){
		return $this->_memory->exist($key);
	}
	
	public function clear($arg1){
		return $this->_memory->clear($arg1);
	}
	
	public function inc($key, $step = 1){
		return $this->_memory->inc($key, $step);
	}
	
	public function dec($key, $step = 1){
		return $this->_memory->dec($key, $step);
	}
	
	public function cas($key, $old, $new){
		return $this->_memory->cas($key, $old, $new);
	}
	
	public function info($arg1, $arg2){
		return $this->_memory->info($arg1, $arg2);
	}
}