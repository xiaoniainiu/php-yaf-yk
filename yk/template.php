<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yk
 *  template.php 下午9:44:32 UTF-8
 */

namespace yk;

final class template{
	
	private $_func_store;	//方法查找替换存储区
	private $_func_index;	//方法查找替换索引
	private $_subtemplate_list;	//动态模版
	
	const VAR_REGEXP = '((\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)';
	const CONST_REGEXP = '([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)';
	
	private $_cssdata = [];
	
	public function __construct(){
		$this->_func_store = ['search' => [], 'replace' => []];
		$this->_subtemplate_list = [];
		$this->_func_index = 0;
	}
	
	public function parse($tplfile, $tpl, $path){
		
		$fp = fopen($tplfile, 'rb');
		if ($fp) {
			$content = fread($fp, filesize($tplfile));
			fclose($fp);
		}else{
			throw new error("$tpl template file don't read!");
		}
		
		//变量
		$var_regexp = self::VAR_REGEXP;
		//常量
		$const_regexp = self::CONST_REGEXP;
		
		//解析动态模版, 最高允许8层
		$i = 0;
		while ($i < 8 && strpos($content, '{subtemplate') !== false){
			$content = preg_replace_callback('/[\n\r\t]*(?:\<\!\-\-)?\{subtemplate\s+(.+?)\}(\-\-\>)?[\r\r\t]*/is', function ($matches) use ($path){
				$content = implode('', file(view_tplfile($matches[1])));
				if ($content){
					$this->_subtemplate_list[] = $matches[1];
					return $content;
				}else{
					return "<!--{$matches[1]} no find-->";
				}
			}, $content);
			$i++;
		}
		unset($i);
		
		//去掉tab
		$content = preg_replace('/([\n\r])+\t+/s', "$1", $content);
		//统一{}和<!--{}-->
		$content = preg_replace('/\<\!\-\-(\{.+?\})\-\-\>/s', "$1", $content);
		
		//保存不解析的内容
		$content = preg_replace_callback('/[\n\r\t]*\{html\}(.+?)\{\/html\}/is',
				function ($matches){
					$this->_func_store['search'][$this->_func_index] = $search = '<!--FUNC-HTML_'.$this->_func_index.'-->';
					$this->_func_store['replace'][$this->_func_index] = $matches[1];
					$this->_func_index ++;
					return $search;
			}, $content);
		
		//解析date
		$content = preg_replace_callback('/[\n\r\t]*\{date\s+(.+?)(?:\s+(.+?))?\s*\}/is', 
				function ($matches){
					if (isset($matches[2])) {
						return $this->_ay_func("$matches[1], $matches[2]", 'ygmdate');
					}else{
						return $this->_ay_func("$matches[1]", 'ygmdate');
					}
			}, $content);
		
		//解析echo，php原形echo
		$content = preg_replace_callback('/[\n\r\t]*\{echo\s+(.+?)(?:;)?[\n\r\t]*\}/is', 
				function ($matches){
					$echo = $this->_addquote($matches[1]);
					$this->_func_store['search'][$this->_func_index] = $search = '<!--FUNC-ECHO_'.$this->_func_index.'-->';
					$this->_func_store['replace'][$this->_func_index] =  "<?php echo $echo;?>";
					$this->_func_index ++;
					return $search;
			}, $content);
		
		//解析eval，将内容当作php代码
		$content = preg_replace_callback('/[\n\r\t]*\{eval\s+(.+?)\s*\}/is', 
				function ($matches){
					$eval = $this->_addquote($matches[1]);
					$this->_func_store['search'][$this->_func_index] = $search = '<!--FUNC-EVAL_'.$this->_func_index.'-->';
					$this->_func_store['replace'][$this->_func_index] =  "<?php $eval;?>";	//后面加上;总是没错的
					$this->_func_index ++;
					return $search;
			}, $content);
		
		//解析yaf\registry里的值
		$content = preg_replace_callback('/\{G\s+([\w\/]+)\s*\}/s', 
				function ($matches){
					$keys = trim($matches[1], '/');
					return $this->_ay_func("'$keys'", 'g');
			}, $content);
		
		//解析CSS
		$content = preg_replace_callback('/\{\-css\-\}/s', 
				function ($matches){
					$request = \yk::app()->getDispatcher()->getRequest();
					$curmca = strtolower($request->getModuleName().'/'.$request->getControllerName().'/'.$request->getActionName());
					
					$cssdata = file_get_contents(view_cachefile('module', '.css'));
					
					$common_cache = view_cachefile('common', '.css');
					
					$module_css[] = file_exists($common_cache) ? file_get_contents($common_cache) : '';
					
					$cssdata = preg_replace_callback('/\[(.+?)\](.+?)\[end\]/s', 
							function ($m) use ($curmca, &$module_css){
								foreach (explode(',', $m[1]) as $name){
									if (strpos($curmca, trim($name)) === 0){
										$module_css[] = $m[2];
									}
								}
								return '';
						}, $cssdata);
					
					$cssname = g('_config/view/name').'_'.md5(APP_NAME.'/'.$curmca).'.css';
					if (!empty($module_css)){
						$fp = fopen(MAIN_PATH.'/public/css/'.$cssname, 'wb');
						if ($fp){
							fwrite($fp, implode('', $module_css));
							fclose($fp);
						}else{
							throw new error("css module can't write to ./css path");
						}
						return '<link rel="stylesheet" type="text/css" href="/css/'.$cssname.'" />';
					}else{
						return '';
					}
					
			}, $content);
		
		//变量替换
		$content = preg_replace('/\{(\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s', "<?=$1?>", $content);
		
		$content = preg_replace_callback("/$var_regexp/s", 
				[$this, '_ay_var_revise'], 
				$content
			);
		
		$content = preg_replace_callback("/\<\?\=\<\?\=$var_regexp\?\>\?\>/s", 
				function ($matches){
					return $this->_addquote('<?='.$matches[1].'?>');
			}, $content);
		
		//----------------------------解析end-----------------------------------------
		
		//动态模版是包含的，所以需要额外检查是否更新了
		$header = '';
		if (!empty($this->_subtemplate_list)){
			$header .= "\n0 ";
			foreach ($this->_subtemplate_list as $file){
				$header .= "|| view_ckreftpl('$tpl', '$file', '".time()."')\n";
			}
			$header .= ';';
		}
		
		//加上头信息
		$content = "<?php {$header}?>\n$content";
		
		//解析嵌套模版
		$content = preg_replace_callback('/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/is', 
				function ($matches){
					return '<?php include view_template(\''.$matches[1].'\');?>';
			}, $content);
		
		//解析if elseif else /if
		$content = preg_replace_callback('/([\n\r\t]*)\{if\s*(.+?)\s*\}([\n\r\t]*)/is', 
				function ($matches){
					return $this->_stripvtags($matches[1].'<?php if('.$matches[2].') { ?>'.$matches[3]);
			}, $content);
		
		$content = preg_replace_callback('/([\n\r\t]*)\{elseif\s*(.+?)\s*\}([\n\r\t]*)/is', 
				function ($matches){
					return $this->_stripvtags($matches[1].'<?php }elseif('.$matches[2].') { ?>'.$matches[3]);
			}, $content);
		
		$content = preg_replace('/([\n\r\t]*)\{else\s*\}([\n\r\t]*)/is', "$1<?php }else { ?>$2", $content);
		$content = preg_replace('/([\n\r\t]*)\{\/if\s*\}([\n\r\t]*)/is', "$1<?php } ?>$2", $content);
		
		//解析foreach // foreach[(]$array [as] [$k] [=>] $v[)]  其中[]内可省略
		//loop 是 foreach的别名
		$content = preg_replace('/\{foreach(.+?)\}/is', "{loop$1}", $content);
		//抽出()符号
		$content = preg_replace('/[\n\r\t]*\{loop[\s\(]*(.+?)[\s\)]*\}/is', "{loop $1}", $content);
		//抽出as
		$content = preg_replace('/\{loop[\s\(]*(.+?)\s+as\s+(.+?)[\s\)]*\}/is', "{loop $1 $2}", $content);
		//抽出=>
		$content = preg_replace('/\{loop[\s\(]*(.+?)\s*\=\>\s*(.+?)[\s\)]*\}/is', "{loop $1 $2}", $content);
		
		//正式解析loop
		$content = preg_replace_callback('/\{loop\s+(\S+)\s+(\S+)\}/is', 
				function ($matches){
					$str = "<?php if(is_array({$matches[1]}) && !empty({$matches[1]})) foreach({$matches[1]} as {$matches[2]} ){ ?>";
					return $this->_stripvtags($str);
			}, $content);
		
		$content = preg_replace_callback('/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/is', 
				function ($matches){
					$str = "<?php if(is_array({$matches[1]}) && !empty({$matches[1]})) foreach({$matches[1]} as $matches[2] => $matches[3] ){ ?>";
					return $this->_stripvtags($str);
			}, $content);
		
		//解析loop foreach 的空数据
		//解析empty
		$content = preg_replace('/\{empty\}/', "<?php }else{ ?>", $content);
		
		$content = preg_replace('/([\n\r\t]*)\{\/(?:foreach|loop)\s*\}([\n\r\t]*)/is', "$1<?php } ?>$2", $content);
		
		//常量替换, 避免污染表达式,以及特定的符号
		$content = preg_replace("/\{$const_regexp\}/s", "<?=$1?>", $content);
		
		//恢复方法
		if ($this->_func_index > 0){
			$content = str_replace($this->_func_store['search'], $this->_func_store['replace'], $content);
		}
		
		/*恢复<?=$var?> */
		$content = preg_replace('/\<\?\=(.+?)\?\>/is', "<?php echo $1; ?>", $content);
		
		/*去掉相邻的?><?php  */
		$content = preg_replace('/\?\>[\n\r]*\<\?php/is', " ", $content);
		
		return $content;
	}
	
	private function _ay_var_revise($matches){
		$var = $matches[1];
		$varstr = $var; $extrastr = '';
		//对中括号进行修正,避免过多匹配产生的错误
		if (strpos($var, '[') !== false){
			$cut_pos = $this->_ay_var_revise_search($var);
			if ($cut_pos > -1){
				$varstr = substr($var, 0, $cut_pos);
				$extrastr = substr($var, $cut_pos);
			}
		}
	
		if (isset($extrastr[2])) {
			$extrastr = preg_replace_callback("/".self::VAR_REGEXP."/s",
					[$this, '_ay_var_revise'],
					$extrastr
			);
		}
	
		return'<?='.$this->_addquote($varstr).'?>'.$extrastr;
	}
	
	private function _ay_var_revise_search($content){
		$start_tag = '[';
		$end_tag = ']';
		$s_pos = $this->_ay_var_revise_pos($content, $start_tag);
		$e_pos = $this->_ay_var_revise_pos($content, $end_tag);
		$s_next_pos = $e_next_pos = 0;
		$cut_pos = -1;
		while (true){
			if ($s_pos !== false && $e_pos !== false  && $s_pos < $e_pos){
				$s_next_pos = $s_pos; $e_next_pos = $e_pos;
				$s_pos = $this->_ay_var_revise_pos($content, $start_tag, $s_pos + 1);
				$e_pos = $this->_ay_var_revise_pos($content, $end_tag, $e_pos + 1);
				if (!in_array(substr($content, $e_next_pos + 1, 1), ['[', ']'])) {
					$s_pos = false;		//修正如这样的字符串设别问题	 adj$ad[a]jdsk[c]
				}
			}else{
				$cut_pos = $e_next_pos + 1;
				break;
			}
		}
		return $cut_pos;
	}
	
	private function _ay_var_revise_pos(&$content, $tag, $start_pos = 0){
		return stripos($content, $tag, $start_pos);
	}
	
	private function _ay_func($args, $func, $prefunc = 'echo'){
		$this->_func_store['search'][$this->_func_index] = $search = '<!--FUNC-'.strtoupper($func).'_'.$this->_func_index.'-->';

		$this->_func_store['replace'][$this->_func_index] =  "<?php $prefunc $func($args);?>";
		
		$this->_func_index ++;
		return $search;
	}
	
	//对数组变量的键值进行加上引号
	private function _addquote($var){
		$var = preg_replace('/\[[\'\"]*([a-zA-Z0-9_\-\.\x7f-\xff]+)[\'\"]*\]/', "['$1']", $var);
		return str_replace('\\"', '"', $var);
	}
	
	//去除变量二次污染
	private function _stripvtags($expr, $statement = '') {
		$expr = str_replace("\\\"", "\"", preg_replace('/\<\?\=\s*(\$.+?)\?\>/s', "$1", $expr));
		$statement && $statement = str_replace("\\\"", "\"", $statement);
		return $expr.$statement;
	}
	
	
}