<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf_yk
 *  mysql.php 下午8:59:30 UTF-8
 */

namespace yk\mysql;

abstract class database extends \yk\table{
	
	protected $_cache_ok = false;	//可缓存?
	protected $_cache_ttl = -1;		//缓存的时间
	protected $_cache_prename = '';	//缓存前缀
	
	protected $_primary_key = null;	//主键, 聚合主键时以逗号分开
	
	public function read(){
		return 1;
	}
	
	public function write(){
		return 1;
	}
	
	protected function cacheInit(){
		if (!$this->_cache_ok && $this->_cache_ttl > -1){
			$this->_cache_ok = ycache('check');
			if (empty($this->_cache_prename)){
				$this->_cache_prename = $this->_name;
			}
		}
	}
	
	//php5.4 array_combine() reutrn false
	protected function _keyLinkVal($val){
		static $key = null;
		if (is_null($key)){
			$key = explode(',', str_replace(' ', '', $this->_primary_key));
		}
		
		if ($val === false) return $key;
		
		$ret = array_combine($key, (array)$val);
		
		if (!$ret){
			throw new \yk\error("the params num is not equal to ".$this->table()." table primary key");
		}
		return $ret;
	}
	
	protected function _cacheKey($key){
		return $this->_cache_prename.implode('_', (array)$key);
	}
	
	public function beginWrite(){
		return instance::begin($this->write());
	}
	
	public function beginRead(){
		return instance::begin($this->read());
	}
	
	public function commitWrite(){
		return instance::commit($this->write());
	}
	
	public function commitRead(){
		return instance::commit($this->read());
	}
	
	public function rollbackWrite(){
		return instance::rollback($this->write());
	}
	
	public function rollbackRead(){
		return instance::rollback($this->read());
	}
	
	//启用cache时, 需要强制将所有字段字入, 避免get时找不到
	public function insert($key, $data, $return_last_id = false, $silent = false){
		$data = array_merge($data, $this->_keyLinkVal($key));
		$ret = instance::insert($this->read(), $this->table(), $data, $return_last_id, $silent);
		if ($ret !== false && $this->_cache_ok){
			$this->_cacheSet($this->_cacheKey($key), $data, $this->_cache_ttl);
		}
		return $ret;
	}
	
	//启用cache时, 需要强制将所有字段字入, 避免get时找不到
	public function replace($key, $data, $silent = false){
		$data = array_merge($data, $this->_keyLinkVal($key));
		$ret = instance::replace($this->write(), $this->table(), $data);
		if ($ret && $this->_cache_ok){
			$this->_cacheSet($this->_cacheKey($key), $data, $this->_cache_ttl);
		}
		return $ret;
	}
	
	//启用cache时, 需要强制将所有字段字入, 避免get时找不到
	public function duplicate($key, $data, $silent = false){
		$val = $this->_keyLinkVal($key);
		$ret = instance::duplicate($this->write(), $this->table(), $val, $data, $silent);
		if ($ret && $this->_cache_ok){
			$this->_cacheSet($this->_cacheKey($key), array_merge($data, $val), $this->_cache_ttl);
		}
		return $ret;
	}
	
	public function update(array $data, $key, $return_affected_rows = false, $silent = false){
		$ret = instance::update($this->write(), $this->table(), $data, $this->_keyLinkVal($key), $return_affected_rows, $silent);
		if ($ret && $this->_cache_ok){
			$this->_cacheDel($this->_cacheKey($key));
		}
		return $ret;
	}
	
	public function delete($key, $return_affected_rows = false, $silent = false){
		$ret = instance::delete($this->write(), $this->table(), $this->_keyLinkVal($key), $return_affected_rows, $silent);
		if ($ret && $this->_cache_ok){
			$this->_cacheDel($this->_cacheKey($key));
		}
		return $ret;
	}
	
	public function one($key, $for_update = false, $force_from_db = false){
		if ($force_from_db || false === ($row = $this->_cacheGet($this->_cacheKey($key)))){
			$row = instance::one($this->read(), "select * from %t where ".instance::implode($this->_keyLinkVal($key)), [$this->table()], $for_update);
			if (!empty($row) && $this->_cache_ok){
				$this->_cacheSet($this->_cacheKey($key), $row, $this->_cache_ttl);
			}
		}
		return $row;
	}
	
	public function all(){
		return instance::more($this->read(), "select * from %t where 1 limit 1000", [$this->table()]);
	}
	
	public function more($sql, array $args, $index = null, $forupdate = false, $silent = false){
		return instance::more($this->read(), $sql, $args, $index, $forupdate, $silent);
	}
	
	//$compare用于比较返回的值与当前值是否为同一条数据
	public function exist($args, $compare = null){
		$row = instance::one($this->read(), "select * from %t where ".instance::implode($args, 'AND'), [$this->table()]);
		if (empty($row)){
			return false;
		}else{
			if (!$compare){
				return true;
			}else{
				foreach ($compare as $k => $v){
					if ($row[$k] != $v){
						return true;
					}
				}
				return false;
			}
		}
	}
	
}