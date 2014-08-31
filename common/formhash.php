<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  formhash.php  上午10:16:07  UTF-8
 */

namespace common;

class formhash{
	
	public static function get(){
		$uid = g('uid');
		$str1 = pack('VVV', $uid, date('w'), date('d'));
		$request = \yk::app()->getDispatcher()->getRequest();
		$str2 = $request->getModuleName();
		$str3 = $request->getControllerName();
		return substr(md5($str1.$str2.$str3), 0, -8);
	}
	
	public static function check($key = 'formhash'){
		$val = $_POST[$key];
		return $val == self::get();
	}
	
	public static function getSeccode($len){
		$allowcode = 'abcdefghijkmnpqxyz23456789ABCDEFGHJKLMNOPQXYZ';
		$seccode = '';	$max = strlen($allowcode) - 1;
		for ($i=0; $i<$len; $i++){
			$seccode .= $allowcode[mt_rand(0, $max)];
		}
		return $seccode;
	}
	
	public static function setSeccode($name, $value, $ttl = 1800) {
		cookie("seccode_$name", auth::encode($value), $ttl);
	}
	
	public static function checkSeccode($name, $value){
		$cookie = cookie("seccode_$name");
		if ($cookie){
			$auth = auth::decode($cookie);
			return strtolower($auth) == strtolower($value);
		}else{
			return false;
		}
	}
	
	public static function clearSeccode($name){
		return cookie($name, '', -1);
	}
}