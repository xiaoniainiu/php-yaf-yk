<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  controller.php 上午11:58:19 UTF-8
 */

namespace yk;

abstract class controller extends \Yaf\Controller_Abstract{
	
	public function appendData($k, $v = null){
		$view = $this->getView();
		$view->assign($k, $v);
	}
	
	public function json($code, $msg, $data = null, $args = null){
		
		$this->appendData('code', $code);
		$this->appendData('msg', $msg);
		is_null($data) or $this->appendData('data', $data);
		is_null($args) or $this->appendData($args);
	}
	
	public function showmessage($code, $msg){
		$this->appendData('code', $code);
		$this->appendData('msg', $msg);
		$this->disableView();
		$this->getView()->display('/common/showmessage.html');
	}
	
	public function disableView(){
		\yk::app()->getDispatcher()->disableView();
	}
	
}