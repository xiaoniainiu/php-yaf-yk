<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  Bootstrap.php 上午1:56:45 UTF-8
 */

class bootstrap extends \application\bootstrap{
	
	public function _initFile(){
		import('/library/func.php');
	}
	
	public function _initUser(){
		
		$user = ['uid' => 0, 'username' => '', 'ulevel' => 0, 'msisdn' => 0];
		
		$auth = cookie('auth');
		if ($auth){
			list($uid, $ip, $hash, $expire) = explode("\t", common\auth::decode($auth));
			if ($uid > 0){
				model('sys_session')->beginRead();
				$session = model('sys_session')->one($uid, true);
				if (!empty($session) && $session['ttl'] > TIMESTAMP && $hash == $session['hash']){
					model('sys_session')->beginWrite();
					$member = model('sys_member')->one($uid);
					if (!empty($member)){
						$user = ['uid' => $member['uid'], 'username' => $member['uname'], 'ulevel' => $member['ulevel'], 'msisdn' => $member['msisdn']];
						
						model('sys_session')->duplicate($uid, array(
							'lgip' => ip2long(g('_clientip')),
							//'ttl' => TIMESTAMP + 1800
						));
						
					}
					model('sys_session')->commitWrite();
				}
				model('sys_session')->commitRead();
			}
		}
		
		foreach ($user as $k => $v){
			g($k, $v, true);
		}
		
	}
	
	public function _initSetting(){
		g('setting', [
			'sitename' => 'YK核心框架'
		]);
	}
}