<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  error.php 上午11:57:24 UTF-8
 */

namespace yk;

final class error extends \Yaf\Exception{
	
	public function __construct($msg, $code = 700){
		parent::__construct($msg, $code);
	}
	
}