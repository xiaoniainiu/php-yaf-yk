<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw
 *  https://github.com/shukean/php-yk
 *  cache.php 下午9:41:55 UTF-8
 */

namespace yk;

class cache{
	
	public function __construct(){
		//
	}
	
	public function isEnable(){
		return function_exists('apcu_enabled') && apcu_enabled();
	}
	
	public function set($key, $value, $ttl){
		if (is_array($key)) {
			apcu_store($key, null, $ttl);
		}else{
			apcu_store($key, $value, $ttl);
		}
	}
	
	public function get($key){
		$return = apcu_fetch($key, $success);
		if ($success !== false) {
			return $return;
		}else{
			return is_array($key) ? array() : false;
		}
	}
	
	public function del($key){
		if (is_array($key)) {
			foreach ($key as $k){
				apcu_delete($k);
			}
		}else{
			apcu_delete($key);
		}
	}
	
	//如果为数组时将返回存在的键值数值，否则为布尔值
	public function exist($key){
		return apcu_exists($key);
	}
	
	public function clear($isUser = true){
		return apcu_clear_cache($isUser ? 'user' : '');
	}

	public function inc($key, $step = 1){
		$value = apcu_inc($key, $step, $success);
		return $success ? $value : false;
	}
	
	public function dec($key, $step = 1){
		$value = apcu_dec($key, $step, $success);
		return $success ? $value : false;
	}
	
	public function cas($key, $old, $new){
		return apcu_cas($key, $old, $new);
	}
	
	public function info($type = 'user', $limit = false){
		return apcu_cache_info($type, $limit);
	}
}