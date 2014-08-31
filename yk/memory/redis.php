<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  redis.php 2014-5-20
 */

namespace yk\memory;

class redis implements base{
	
	private $_object;
	private $_enabled;
	
	public function __construct(){
		$config = ['pconnect' => 0, 'server' => '', 'port' => '', 'timeout' => '', 'reserved' => null];
		$this->_object = new \Redis();
		
		if ($config['pconnect']){
			$this->_enabled = $this->_object->pconnect($config['server'], $config['port'], $config['timeout']);
		}else{
			$this->_enabled = $this->_object->connect($config['server'], $config['port'], $config['timeout']);
		}
		
		if ($this->_enabled) {
			$this->_object->setOption(Redis::OPT_SERIALIZER, $config['serializer'] ? Redis::SERIALIZER_PHP : Redis::SERIALIZER_NONE);
		}
	}
	
	public function isEnable(){
		return $this->_enabled;
	}
	
	public function get($key){
		if (is_array($key)){
			$result = $this->_object->getMultiple();
			$response = [];
			foreach ($key as $k => $v){
				$response[$v] = $result[$k];
			}
			return $response;
		}else{
			return $this->_object->get($key);
		}
	}
	
	public function set($key, $value, $expire = 0){
		if ($expire){
			return $this->_object->setex($key, $expire, $value);
		}else{
			return $this->_object->set($key, $value);
		}
	}
	
	public function del($key){
		return $this->_object->delete($key);
	}
	
	public function inc($key, $value = 1){
		return $this->_object->incr($key, $value);
	}
	
	public function dec($key, $value = 1){
		return $this->_object->decr($key, $value);
	}
	
	public function clear(){
		return $this->_object->flushAll();
	}
	
	public function cas($key, $old, $new){
		if ($this->_object->get($key) == $old) {
			return $this->_object->setex($key, $this->_object->ttl($key), $new);
		}
		return false;
	}
	
	public function exist($key){
		return $this->_object->exists($key);
	}
	
	public function info($arg1, $arg2){
		return ;
	}
}