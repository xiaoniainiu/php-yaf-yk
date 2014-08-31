<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  Error.php  下午8:40:25  UTF-8
 */

class errorController extends yk\controller{
	
	public function errorAction($exception){
		
		$this->appendData([
			'code' => $exception->getCode(), 
			'msg' => $exception->getMessage()
		]);
	}
	
}