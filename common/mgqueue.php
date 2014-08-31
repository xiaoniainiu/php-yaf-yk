<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  mgqueue.php  下午9:50:19  UTF-8
 */

namespace common;

class mgqueue{
	
	const Q_SMS = 1;			//短信
	const Q_COUPON = 2;			//生成优惠券
	const Q_NEW_ORDER = 3;		//新订单
	
	public static $error = null;
	
	public static function insert($type, $msg, $uid = 0){
		self::$error = null;
		
		return t('queue_message')->ins([
			'type' => $type,
			'uid' => $uid,
			'content' => is_array($msg) ? serialize($msg) : $msg,
			'attime' => TIMESTAMP
		]);
	}
	
	
}