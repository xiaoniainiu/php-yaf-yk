<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  view.php 上午11:59:04 UTF-8
 */

namespace yk;

class view implements \Yaf\View_Interface{
	
	private $_tpl_val = [];
	private $_script_path = '';
	
	private $_ext;
	
	public function __construct(){
		$this->_script_path = APP_PATH.'/views';
		if (!file_exists(MAIN_PATH.'/data/view/'.APP_NAME)){
			mkdir(MAIN_PATH.'/data/view/'.APP_NAME, 0755, true);
		}
	}
	
	private function _tpl2path($tpl){
		if (strpos($tpl, ':') > 0){
			list($name, $tpl) = explode(':', $tpl);
		}else{
			$name = g('_config/view/name');
		}
		return $name.'/'.$tpl;
	}
	
	public function getCache($view_path, $ext = '.php'){
		return MAIN_PATH.'/data/view/'.APP_NAME.'/'.str_replace('/', '_', $this->_tpl2path($view_path)).$ext;
	}
	
	public function getTpl($view_path){
		return $this->_script_path.'/'.$this->_tpl2path($view_path);
	}
	
	public function checkCss(){
		$dir = $this->_script_path.'/'.g('_config/view/name').'/common/';
		$pdir = dir($dir);
		$cf_modules = [];
		$cf_time = 0;
		while (false !== ($entry = $pdir->read()) ){
			if (strpos($entry, 'module.css') !== false) {
				$cf_modules[] = $dir.$entry;
				$cf_time = max($cf_time, filemtime($dir.$entry));
			}
		}
		
		$cache = $this->getCache('module', '.css');
		if ($cf_time > 0 && (!file_exists($cache) || filemtime($cache) < $cf_time)){
			$data = [];
			foreach ($cf_modules as $m){
				$cssdata = preg_replace('/\/\*\*\s*(.+?)\s\*\*\//is', "[$1]", implode('', file($m)));
				$cssdata = preg_replace(array('/\s*([,;:\{\}])\s */', '/[\t\n\r]/', '/\/\*.+?\*\//'), array('\\1', '',''), $cssdata);
				$cssdata = preg_replace('/\[(.+?)\]\[end\]/', '', $cssdata);
				$data[] = $cssdata;
			}
			
			$fp = fopen($cache, 'wb');
			if ($fp){
				fwrite($fp, implode('', $data));
				fclose($fp);
			}else{
				throw new error("css cache cache file can't write!");
			}
		}
		
		$common_css = $dir.'common.css';
		if (file_exists($common_css)){
			$cache = $this->getCache('common', '.css');
			if (!file_exists($cache) || filemtime($cache) < filemtime($common_css)){
				$cssdata = preg_replace('/\/\*\*\s*(.+?)\s\*\*\//is', "[$1]", implode('', file($common_css)));
				$fp = fopen($cache, 'wb');
				if ($fp){
					fwrite($fp, $cssdata);
					fclose($fp);
				}else{
					throw new error("css cache cache file can't write!");
				}
			}
		}
	}
	
	public function checkRender($view_path, $check_view = '', $time = 0){
		static $tplrefresh = null;
		if (is_null($tplrefresh)){
			$tplrefresh = g('_config/view/tplrefresh');
		}
		
		$cache_file = $this->getCache($view_path);
		$tpl_file = $this->getTpl($view_path);
		$check_file = $check_view ? $this->getTpl($check_view) : 0;
		
		if (!file_exists($cache_file) || $tplrefresh == 1 
				|| $tplrefresh == 2 && filemtime($tpl_file) > filemtime($cache_file) 
				|| $check_file && filemtime($check_file) > $time){
			
			$this->checkCss();
			
			$template = new template();
			$content = $template->parse($tpl_file, $view_path, $this->_script_path);
			
			$fp = fopen($cache_file, 'wb');
			if ($fp){
				fwrite($fp, $content);
				fclose($fp);
			}else{
				throw new error("$view_path template cache file can't write!");
			}
			
			//return $content;
		}else{
			//return file_get_contents($cache_file);
		}
		
		return $cache_file;
	}
	
	public function render($view_path, $tpl_vars = NULL){
		
		if (is_array($tpl_vars)){
			foreach ($tpl_vars as $k => $v){
				$this->_tpl_val[$k] = $v;
			}
		}
		
		if (g('_inajax')){
			return !empty($this->_tpl_val) ? json_encode($this->_tpl_val, JSON_UNESCAPED_UNICODE) : null;
		}else{
			g('_formhash', \common\formhash::get());
			if (strpos($view_path, '/') === 0){
				$content = $this->checkRender($view_path);
			}else{
				$module = strtolower(\yk::app()->getDispatcher()->getRequest()->getModuleName());
				$content = $this->checkRender($module.'/'.$view_path);
			}
			try{
				ob_start();
				extract($this->_tpl_val);
				/*eval('?>'. $content);*/
				include $content;
			}catch(\Exception $e){
				ob_get_clean();
				throw $e;
			}
			$output = ob_get_clean();
			return $output;
		}
	}
	
	public function display($view_path, $tpl_vars = NULL){
		echo $this->render($view_path, $tpl_vars);
	}
	
	public function assign($name, $value = NULL){
		if (is_array($name)){
			$this->_tpl_val = array_merge($this->_tpl_val, $name);
		}else{
			$this->_tpl_val[$name] = $value;
		}
	}
	
	public function setScriptPath($view_directory){
		$this->_script_path = $view_directory;
		return true;
	}
	
	public function getScriptPath(){
		return $this->_script_path;
	}

}