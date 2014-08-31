<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  table.php 下午8:51:00 UTF-8
 */

namespace yk;

abstract class table{
	
	abstract public function table();
	
	abstract public function read();
	
	abstract public function write();
	
	protected function _cacheSet($key, $value, $ttl){
		$ret = ycache('set', $key, $value, $ttl);
		if (!$ret){
			log::runlog('cache failure '.ygmdate(TIMESTAMP), 'sys');
			ycache('del', $key);
		}
	}
	
	protected function _cacheGet($key){
		return ycache('get', $key);
	}
	
	protected function _cacheDel($key){
		return ycache('del', $key);
	}
}