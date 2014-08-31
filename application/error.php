<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  error.php  下午9:09:44  UTF-8
 */

namespace application;

class error extends \yk\controller{
	
	public function errorAction($exception){
	
		$debugs = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 50);
		
		$log = [];
		$log[] = '['.$exception->getCode().']'.$exception->getMessage();
		$log[] = '------------------------------------------------------------';
		$log[] = sprintf("%-4s%-20s%-6s%-s", '#', 'File', 'Line', 'Method');
		$i = 1;
		foreach ($debugs as $row){
			$method = (!empty($row['class']) ? $row['class'].$row['type'] : '').$row['function'];
			
			$method .= '(';
			foreach ($row['args'] as $arg){
				if (is_array($arg)){
					$method .= 'array, ';
				}elseif (is_object($arg)){
					$method .= 'object, ';
				}else{
					$method .= $arg.', ';
				}
			}
			$method .= 'NULL)';
			
			$log[] = sprintf("%-4s%-20s%-6s%-s", $i, str_replace(MAIN_PATH, '', $row['file']), $row['line'], $method);
			$i++;
		}
		
		\yk\log::runlog(implode("\n", $log), 'exception', true);
		
		$this->appendData([
				'code' => $exception->getCode(),
				'msg' => $exception->getMessage()
			]);
	}
	
}