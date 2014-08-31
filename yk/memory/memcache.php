<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  memcache.php 2014-5-20
 */

namespace yk\memory;

class memcache implements base{
	
	private $_object;
	private $_isenable;
	
	public function __construct(){
		$config = array(
			'server' => '',
			'port' => '',
			'timeout' => 30,
			'pconnect' => 0
		);
		$this->_object = new \Memcache;
		
		if ($config['pconnect']){
			$this->_isenable = $this->_object->pconnect($config['server'], $config['port'], $config['timeout']);
		}else{
			$this->_isenable = $this->_object->connect($config['server'], $config['port'], $config['timeout']);
		}
	}
	
	public function isEnable(){
		return $this->_isenable;
	}
	
	public function get($key){
		return $this->_object->get($key);
	}
	
	public function set($key, $value, $expire = 0){
		return $this->_object->set($key, $value, MEMCACHE_COMPRESSED, $expire);
	}
	
	public function del($key){
		return $this->_object->delete($key);
	}
	
	public function inc($key, $value = 1){
		return $this->_object->increment($key, $value);
	}
	
	public function dec($key, $value = 1){
		return $this->_object->decrement($key, $value);
	}
	
	public function clear(){
		return $this->_object->flush();
	}
	
	public function cas($key, $old, $new){
		if ($this->_object->get($key) == $old) {
			return $this->_object->replace($key, $new);
		}
		return false;
	}
	
	public function exist($key){
		return $this->get($key);
	}
	
	public function info($arg1, $arg2){
		return $this->_object->getstats($arg1, $arg2);
	}
}