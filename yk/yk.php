<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  yk.php 上午11:59:13 UTF-8
 */

define('YK_CORE_ROOT', dirname(__FILE__));
define('MAIN_PATH', substr(YK_CORE_ROOT, 0, -3));

final class yk{
	
	private static $_yaf_app = null;
	
	public static function app(){
		return self::$_yaf_app;
	}
	
	public function __construct($environ = 'product'){
	
		define('APP_PATH', MAIN_PATH.'/application/'.$environ);
		define('APP_NAME', $environ);
		
		if (!self::$_yaf_app){
			self::$_yaf_app = new Yaf\Application(MAIN_PATH.'/conf/app.ini', $environ);
		}
		
		if (!self::$_yaf_app){
			die('yaf init error');
		}
		
		spl_autoload_register([__CLASS__, 'autoload'], true, true);
		
		(new yk\core())->init(self::$_yaf_app);
		
	}
	
	public function run($bootstrap = false){
		if ($bootstrap){
			self::$_yaf_app->bootstrap();
		}
		self::$_yaf_app->run();
	}
	
	public function autoload($class){
		$classname = str_replace('\\', '/', ltrim($class, '\\'));
		$classpath = dirname($classname).'/'.str_replace('_', '/', basename($classname)).'.php';
		$file = MAIN_PATH.'/'.$classpath;
		if (file_exists($file)){
			include $file;
		}else{
			$file = MAIN_PATH.'/application/'.$classpath;
			if (file_exists($file)){
				include $file;
			}else{
				throw new Exception("class name $class not found !");
			}
		}
	}
}
