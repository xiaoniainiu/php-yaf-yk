<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  Index.php  下午9:12:48  UTF-8
 */

class indexController extends \yk\controller{
	
	public function indexAction(){
		if (g('uid') < 1){
			$this->redirect('/login');
		}else{
			$this->redirect('/center');
		}
	}
	
}