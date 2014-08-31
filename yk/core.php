<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  core.php 上午11:58:26 UTF-8
 */

namespace yk;

final class core{
	
	public function __construct(){
		//nothing
	}
	
	public function init($yaf_app){
		static $initialized = false;
		if (!$initialized){
			$initialized = true;
			
			\Yaf\Loader::import(YK_CORE_ROOT.'/func.php');
			
			$config = $yaf_app->getConfig()->toArray();
			
			\Yaf\Registry::set('_config', $config);
			
			$level = $config['debug'] ? ($config['debug'] == 1 ? E_ERROR : E_ALL) : 0;
			define('MAIN_DEBUG', $config['debug']);
			if (MAIN_DEBUG){
				ini_set('display_error', 'On');
			}
			error_reporting($level);
			
			define('TIMESTAMP', time());
			\Yaf\Registry::set('_clientip', isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'N/A');
			\Yaf\Registry::set('_clientport', isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : 0);
			
			\Yaf\Registry::set('_gzip', (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false || !function_exists('ob_gzhandler')) ? false : true);
			
			set_timeoffset($config['timeoffset']);
			
			//重置视图
			$view = new view();
			\Yaf\Registry::set('_view', $view);
			$yaf_app->getDispatcher()->setView($view);
			
			define('CHARSET', 'utf-8');
			//end
		}
	}
}