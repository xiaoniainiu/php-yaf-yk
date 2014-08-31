<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  instance.php 下午9:21:39 UTF-8
 */

namespace yk\mysql;

final class instance{
	
	private static $_links = [];
	
	private static $_transactiones = [];
	
	protected static function _connect($id, $force = false){
		static $config = null;
		if (is_null($config)) $config = g('_config/mysql');
		
		if (!isset(self::$_links[$id]) || $force){
			if (isset($config[$id])){
				$cf = $config[$id];
				$link = new \mysqli($cf['host'], $cf['user'], $cf['password'], $cf['dbname'], $cf['port'], null);
				if ($link->connect_errno){
					\yk\log::runlog("[{$link->connect_errno}]{$link->connect_error}", 'sys');
					throw new \yk\error("mysql id $id connect error");
				}
				$link->set_charset($cf['charset']);
				self::$_links[$id] = $link;
			}else{
				throw new \yk\error("mysql config id $id not found !");
			}
		}
		
	}
	
	/**
	 * @param mysql seriver number $id
	 * @return \mysqli
	 */
	public static function link($id){
		self::_connect($id);
		return self::$_links[$id];
	}
	
	public static function query($id, $sql, $args = null, $silent = false){
		if (!empty($args)){
			$newsql = '';
			$i = 0; $pos = 0;
			$count = count($args);
			for($i=0, $len=strlen($sql); $i<$len; $i++){
				if ($sql[$i] != '%'){
					$newsql .= $sql[$i];
				}else{
					switch ($sql[$i+1]){
						case 't':
							$newsql .= '`'.$args[$pos].'`';
							break;
						case 'd':
							$newsql .= intval($args[$pos]);
							break;
						case 's':
							$newsql .= self::quote(is_array($args[$pos]) ? serialize($args[$pos]) : $args[$pos]);
							break;
						case 'f':
							$newsql .= sprintf('%f', $args[$pos]);
							break;
						case 'i':
							$newsql .= $args[$pos];
							break;
						case 'n':
							$newsql .= is_array($args[$pos]) ? implode(',', self::quote($args[$pos])) : self::quote($args[$pos]);
							break;
						default:
							$newsql .= $sql[$i].$sql[$i+1];
							$pos --;
							break;
					}
					$pos ++;
					$i++;
				}
			}
			$sql = $newsql;
		}else{
			$newsql = $sql;
		}
		//echo $newsql, PHP_EOL;
		$result = self::link($id)->query($newsql);
		if (!$result && !$silent){
			$error = self::link($id)->error;
			
			//free trans
			if (!empty(self::$_transactiones)){
				foreach (self::$_transactiones as $_id => $status){
					if ($status){
						self::link($_id)->query("rollback");
					}
				}
			}
			
			\yk\log::runlog($newsql, 'sql');
			throw new \yk\error($error);
		}
		return $result;
	}
	
	public static function getLastInsertId($id){
		return self::link($id)->insert_id;
	}
	
	public static function fetch(\mysqli_result $result){
		return $result->fetch_assoc();
	}
	
	public static function affected_rows($id){
		return self::link($id)->affected_rows;
	}
	
	//SQL_CALC_FOUND_ROWS
	public static function found_rows($id){
		return self::result($id, "SELECT FOUND_ROWS()", []);
	}
	
	public static function insert($id, $table, array $data, $return_last_id = false, $silent = false){
		$sql = "insert into `$table` set ".self::implode($data, ',');
		$result = self::query($id, $sql, null, $silent);
		if ($result && $return_last_id){
			return self::$_links[$id]->insert_id;
		}else{
			return $result;
		}
	}
	
	public static function replace($id, $table, array $data, $silent = false){
		$sql = "replace into `$table` set ".self::implode($data, ',');
		return self::query($id, $sql, null, $silent);
	}
	
	public static function duplicate($id, $table, array $key, array $data, $silent = false){
		$com = self::implode($data);
		$sql = "insert into `$table` set ".self::implode($key).", $com on duplicate key update $com";
		return self::query($id, $sql, null, $silent);
	}
	
	public static function update($id, $table, array $data, array $condition, $return_affected_rows = false, $silent = false){
		$sql = "update `$table` set ".self::implode($data).' where '.self::implode($condition, 'AND');
		$result = self::query($id, $sql, null, false);
		if ($result && $return_affected_rows){
			return self::$_links[$id]->affected_rows;
		}else{
			return $result;
		}
	}
	
	public static function delete($id, $table, array $condition, $return_affected_rows = false, $silent = false){
		$sql = "delete from `$table` where ".self::implode($condition, 'AND');
		$result = self::query($id, $sql, null, $silent);
		if ($result && $return_affected_rows){
			return self::$_links[$id]->affected_rows;
		}else{
			return $result;
		}
	}
	
	public static function one($id, $sql, array $args, $forupdate = false, $silent = false){
		$result = self::query($id, $sql." limit 1".($forupdate ? " for update" : ''), $args, $silent);
		return $result ? $result->fetch_assoc() : [];
	}
	
	public static function more($id, $sql, array $args, $index = null, $forupdate = false, $silent = false){
		$result = self::query($id, $sql.($forupdate ? " for update" : ''), $args, $silent);
		$return = [];
		if ($index){
			while (null !== ($row = $result->fetch_assoc())){
				$return[$row[$index]] = $row;
			}
		}else{
			while (null !== ($row = $result->fetch_assoc())){
				$return[] = $row;
			}
		}
		return $return;
	}
	
	public static function result($id, $sql, array $args, $pos = 0, $silent = false){
		$result = self::query($id, $sql." limit 1", $args, $silent);
		$row = $result->fetch_row();
		return isset($row[$pos]) ? $row[$pos] : null;
	}
	
	public static function lockRead($id, $tables){
		return self::query($id, "lock $tables read");
	}
	
	public static function lockWrite($id, $tables){
		return self::query($id, "lock $tables write");
	}
	
	public static function unlockRead($id, $tables){
		return self::query($id, "unlock $tables read");
	}
	
	public static function unlockWrite($id, $tables){
		return self::query($id, "unlock $tables write");
	}
	
	public static function begin($id){
		foreach ((array) $id as $v){
			if (!isset(self::$_transactiones[$id])){
				self::$_transactiones[$id] = self::query($id, "begin");	
			}
		}
	}
	
	public static function commit($id){
		foreach ((array) $id as $v){
			if (isset(self::$_transactiones[$id]) && self::$_transactiones[$id]){
				self::query($id, "commit");
				unset(self::$_transactiones[$id]);
			}
		}
	}
	
	public static function rollback($id){
		foreach ((array) $id as $v){
			if (isset(self::$_transactiones[$id]) && self::$_transactiones[$id]){
				self::query($id, "rollback");
				unset(self::$_transactiones[$id]);
			}
		}
	}

	public static function implode($data, $glue = ','){
		$glue = ' '.trim($glue).' ';
		$sql = $comma = '';
		foreach ($data as $k => $v){
			$sql .= $comma.self::quoteFields($k).'='.self::quote($v);
			$comma = $glue;
		}
		return $sql;
	}
	
	public static function quote($str, $noarray = false){
		if (is_string($str)){
			return '\''.addcslashes($str, "\n\r\\'\"\032").'\'';
		}elseif (is_int($str) || is_float($str)){
			return '\''.$str.'\'';
		}elseif(is_bool($str)){
			return $str ? '1' : '0';
		}elseif (is_array($str)){
			if($noarray === false) {
				foreach ($str as &$v) {
					$v = self::quote($v, true);
				}
				return $str;
			} else {
				return '\'\'';
			}
		}else{
			return '\'\'';
		}
	}
	
	public static function quoteFields($fields){
		if (strpos($fields, '.') === false){
			return "`$fields`";
		}else{
			list($t, $name) = explode('.', $fields);
			return "$t.`$name`";
		}
	}
	
}