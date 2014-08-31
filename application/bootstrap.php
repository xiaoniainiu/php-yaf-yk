<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  Bootstrap.php  下午6:41:24  UTF-8
 */

namespace application;

class bootstrap extends \Yaf\Bootstrap_Abstract{
	
	public function _initSys(){
		$inajax = isset($_POST['inajax']) ? $_POST['inajax'] : (isset($_GET['inajax']) ? $_GET['inajax'] : isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		g('_inajax', $inajax);
	}
}