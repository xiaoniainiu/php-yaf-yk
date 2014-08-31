<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw
 *  base.php 2014-6-19
 */

namespace yk\memory;

interface base{
	
	public function isEnable();
	
	public function set($key, $value, $ttl);
	
	public function get($key);
	
	public function del($key);
	
	public function exist($key);
	
	public function clear($arg1);
	
	public function inc($key, $step = 1);
	
	public function dec($key, $step = 1);
	
	public function cas($key, $old, $new);
	
	public function info($arg1, $arg2);
}