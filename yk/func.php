<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  func.php 上午11:58:53 UTF-8
 */

/**
 * @param string $model
 * @return yk\mysql\database
 */
function t($model, $app = null){
	static $_models = [], $_app = [];
	if (is_null($app)){
		if (!isset($_models[$model])){
			$model_name = "models\\$model";
			$_models[$model] = new $model_name;
		}
		return $_models[$model];
	}else{
		if (!isset($_app[$app][$model])){
			$model_name = APP_NAME."\\models\\$model";;
			$_app[$app][$model] = new $model_name;
		}
		return $_app[$app][$model];
	}
}

/**
 * @param string $model
 * @return yk\mysql\database
 */
function model($name){
	return t($name, APP_NAME);
}

function v($name, $func = 'string', $msg = null, $extra = null, $type = \yk\input::REQUEST){
	return \yk\input::get($name, $func, $msg, $extra, $type);
}

function vals(array $keys, $callback = null, $args = null){
	$data = \yk\input::multiGet($keys);
	if($callback !== null){
		return call_user_func($callback, $data, $args);
	}else{
		return $data;
	}
}

function vf($value, $method, $extra = null){
	return \yk\filter::verify($value, $method, $extra);
}

//是对Yaf_Registry的封装
function g($key, $value = null, $nullSet = false){
	$sp = explode('/', $key);
	$nullSet = $nullSet || !is_null($value);
	
	$_g = Yaf\Registry::get($sp[0]);;
	$vals = &$_g;
	
	if (Yaf\Registry::has($sp[0]) || $nullSet){
		$i = 1;
		while (isset($sp[$i])){
			$pos = $sp[$i];
			if (!isset($vals[$pos])){
				if ($nullSet) $vals[$pos] = [];
				else return null;
			}elseif (!is_array($vals[$pos]) && $nullSet){
				$vals[$pos] = [];
			}
			$vals = &$vals[$pos];
			$i++;
		}
		if ($nullSet){
			$vals = $value;
			return Yaf\Registry::set($sp[0], $_g);
		}else{
			return $vals;
		}
	}else{
		return null;
	}
}


function cookie($key, $value = null, $life = 0, $prefix = 1, $httponly = false){
	static $cookies = null, $pre = null, $path, $domain;
	if (is_null($pre)){
		$ckconf = g('_config/cookie');
		$pre = $ckconf['cookiepre'];
		$path = $ckconf['cookiepath'];
		$domain = $ckconf['cookiedomain'];
		if ($pre && ($len = strlen($pre))){
			foreach ($_COOKIE as $k => $v){
				if (strpos($k, $pre) === 0){
					$cookies[substr($k, $len)] = $v;
				}else{
					$cookies[$k] = $v;
				}
			}
		}else{
			$cookies = $_COOKIE;
		}
		
	}
	
	if (is_null($value)){
		return isset($cookies[$key]) ? $cookies[$key] : null;
	}else{
		if (!$value && $life < 0){
			$value = ''; $life = -1;
		}
		
		$key = $prefix ? $pre.$key : $key;
		
		$life = $life > 0 ? TIMESTAMP + $life : ($life < 0 ? TIMESTAMP - 31536000 : 0);
		$path = $httponly ? $path.'; HttpOnly' : $path;
		
		$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
		setcookie($key, $value, $life, $path, $domain, $secure, $httponly);
		
		$_COOKIE[$key] = $value;
	}
}

function view_ckreftpl($view, $check = '', $time = 0){
	g('_view')->checkRender($view, $check, $time);
}

function view_cachefile($view, $ext = '.php'){
	return g('_view')->getCache($view, $ext);
}

function view_tplfile($view){
	return g('_view')->getTpl($view);
}

function view_template($view){
	view_ckreftpl($view);
	return view_cachefile($view);
}

function set_timeoffset($offset = 0){
	date_default_timezone_set('Etc/GMT'.($offset > 0 ? '-' : '+' ).abs($offset));
}

function ypage($page, $total, $view_num = 6){
	if ($page > $total) return [];
	if ($total <= $view_num) {
		return range(1, $total, 1);
	}else{
		//在$page的左右二边各显示最大数量
		$num = ceil($view_num / 2);

		$start = $page - $num;
		if ($start > 0) {
			$end = $page + $num;
			if ($total < $end) { //后面不够,用全部补全$num位
				$start = max(1, $start + $total - $end);
				$end = $total;
			}
		}else{
			$start = 1;
			$end = $page + $num + $num - $page + 1;
		}
		while ($start <= $end){
			$return[] = $start++;
		}
		return $return;
	}
}

function yrandom($len, $isnumeric = false){
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $isnumeric ? 10 : 35);
	if (!$isnumeric) {
		$seed .= 'zZ'.strtoupper($seed);
	}else{
		$seed .= '0123456789';
	}

	$max = strlen($seed) - 1;
	$hash = '';
	while ($len-- > 0){
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}

function ycache($cmd, $arg1 = null, $arg2 = null, $arg3 = null){
	static $cache = null;
	if (!$cache) {
		$cache = new \yk\cache();
		if (!$cache->isEnable()) {
			$cache = \yk\memory::getInstance()->getCache();
		}
	}
	if ($cmd == 'check') {
		return is_object($cache);
	}else{
		return $cache ? $cache->$cmd($arg1, $arg2, $arg3) : false;
	}
}

function ytimestamp($datestr){
	if (false !== ($timestamp = strtotime($datestr)) ) {
		return $timestamp;
	}else{
		return false;
	}
}

function ygmdate($timestamp, $format = null){
	if ($format) {
		return date($format, $timestamp);
	}
	static $lang = ['yesterday' => '昨天', 'year' => '年', 'month' => '月', 'day' => '日', 'today' => '今天', 'hour' => '小时',
	'minute' => '分钟', 'second' => '秒', 'ago' => '前', 'about' => '约', 'now' => '刚刚', 'more' => '还有'];
	
	if ($timestamp == 0){
		return 'unknow';
	}
	
	$differ = TIMESTAMP - $timestamp;
	if ($differ >= 0) {
		//过去
		if ($differ > 172800) {	//大于2天
			return $returnstr = date('n'.$lang['month'].'d'.$lang['day'].' H:i', $timestamp);
		}elseif ($differ > 86400){	//天于1天
			return $returnstr = $lang['yesterday'].' '.date('H:i', $timestamp);
		}elseif ($differ > 3600){	//大于1小时
			return $lang['today'].' '.date('H:i', $timestamp);
		}elseif ($differ > 60){		//大于1分钟
			return round($differ / 60).$lang['minute'].$lang['ago'];
		}else{
			return $lang['about'].$differ.$lang['second'].$lang['ago'];
		}
	}else{
		//将来
		$differ = abs($differ);
		if ($differ < 60) {	//1分钟内
			return $differ ? $lang['more'].$differ.$lang['seconed'] : $lang['now'];
		}elseif ($differ < 3600){	//1个小时内
			return $lang['more'].round($differ / 60).$lang['minute'];
		}elseif ($differ < 86400){	//一天内
			return $lang['more'].round($differ / 3600).$lang['hour'];
		}else{
			return date('Y'.$lang['year'].'n'.$lang['month'].'j'.$lang['day'], $timestamp);
		}
	}
}

function fileext($file){
	if (($pos = strrpos($file, '.')) !== false) {
		return substr($file, $pos + 1);
	}
	return null;
}

function import($file){
	Yaf\Loader::import(APP_PATH.$file);
}

function limit_init($pp = 20){
	$data = vals([
		['pi', 'uint'],
		['pp', 'uint']
	]);
	$pi = isset($data['pi']) ? max(intval($data['pi']), 1) : 1;
	$pp = isset($data['pp']) ? intval($data['pp']) : $pp;
	return [($pi - 1) * $pp, $pp, 1, $pi];
}

function url_init($filter = []){
	if (!empty($filter)){
		$arr = parse_url($_SERVER['REQUEST_URI']);
		$query = [];
		if (!empty($arr['query'])){
			parse_str($arr['query'], $query);
			foreach ($filter as $k){
				if (isset($query[$k])){
					unset($query[$k]);
				}
			}
		}
		return rtrim($arr['path'], '/').'?'.http_build_query($query);
	}else{
		return strpos($_SERVER['REQUEST_URI'], '?') !== false ? $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'].'?';
	}
}
