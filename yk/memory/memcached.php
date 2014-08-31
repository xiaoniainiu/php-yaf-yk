<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  memcached.php 2014-5-20
 *      
 *      config:
 *      
 *      'memory' = array{     
 *      	'memcached' => array(
 *      		array('host' => '', 'port' => '11211', 'weight' => 0),
 *      		array('host' => '', 'port' => '11211', 'weight' => 0),
 *      		...
 *      	)
 *      }
 *      
 */

namespace yk\memory;

class memcached implements base{
	
	private $_object;
	private $_isenable;
	
	public function __construct(){
		$config = [
			['host' => '', 'port' => '11211', 'weight' => 0]
		];
		$this->_object = new \Memcached('story_pool');
		$servers = [];
		foreach ($config as $conf){
			$servers[] = [$conf['host'], $conf['port'], $conf['weight']];
		}
		$this->_isenable = $this->_object->addServers($servers);
	}
	
	public function isEnable(){
		return $this->_isenable;
	}
	
	public function get($key){
		if (is_array($key)) {
			return $this->_object->getMulti($key);
		}else{
			return $this->_object->get($key);
		}
		
	}
	
	public function set($key, $value, $expire = 0){
		return $this->_object->set($key, $value, $expire);
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
		return $this->_object->getStats();
	}
}