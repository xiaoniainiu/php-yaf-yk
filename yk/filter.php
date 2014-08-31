<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  filter.php 2014-5-20
 */

namespace yk;

class filter{
	
	public static function verify(&$value, $methodname, $extra = null){
		
		if ($extra !== null) {
			
			$value = trim($value);
			
			if (isset($extra['allow_empty']) && $value === '') {
				return true;
			}
			
			if (isset($extra['len']) || isset($extra['len2'])) {
				if (array_key_exists('len', $extra)) {
					$value_len = self::_cn1_strlen($value);
					$len = $extra['len'];
				}else{
					$value_len = self::_cn2_strlen($value);
					$len = $extra['len2'];
				}
				
				if (strpos($len, ',') === false) {
					$len = ','.$len;
				}
				list($min, $max) = explode(',', $len);
				
				if ($min && $value_len < $min) {
					return false;
				}
				
				if ($max && $value_len > $max) {
					return false;
				}
			}
			
			if (array_key_exists('reg', $extra)) {
				if (($reverse = substr($extra['reg'], 0, 1) ) == '!') {
					if (preg_match(substr($extra['reg'], 1), $value)){
						return false;
					}
				}else{
					if (!preg_match($extra['reg'], $value)) {
						return false;
					}
				}
			}
			
			if (isset($extra['ext']) && function_exists($extra['ext'])) {
				return call_user_func($extra['ext'], $value);
			}
		}
		
		$status = false;
		if ($methodname) {
			if (is_string($methodname) && strpos($methodname, '|') !== false) {
				list($main_ck, $helper_ck) = explode('|', $methodname);
				$main_ck = 'ck_'.$main_ck;
				$helper_ck = 'ck_'.$helper_ck;
				if (method_exists(__CLASS__, $main_ck) && method_exists(__CLASS__, $helper_ck)) {
					if (self::$main_ck($value, $extra, $helper_ck)) {
						return true;
					}
				}else{
					throw new error("filter method $methodname not found");
				}
			}else{
				if (!is_array($methodname)) {
					$methodname = [$methodname];
				}
				foreach ($methodname as $method){
					$method = 'ck_'.$method;
					if (method_exists(__CLASS__, $method)) {
						if (self::$method($value, $extra)) {
							return true;
						}
					}else{
						throw new error("filter method $method not found");
					}
				}
			}
		}
		
		return $status;
	}
	
	public static function is_type($value, $method){
		$method = 'ck_'.$method;
		if (method_exists(__CLASS__, $method)) {
			return self::$method($value);
		}else{
			throw new error("filter $method not find");
		}
	}
	
	protected static function ck_uint($value){
		return $value >= 0 && $value <= 4294967295 ? true : false;
	}
	
	protected static function ck_int($value){
		return $value >= -2147483648 && $value <= 2147483647 ? true : false;
	}
	
	protected static function ck_umediumint($value){
		return $value >= 0 && $value <= 16777215 ? true : false;
	}
	
	protected static function ck_mediumint($value){
		return $value >= -8388608 && $value <= 8388607 ? true : false;
	}
	
	protected static function ck_usmallint($value){
		return $value >= 0 && $value <= 65535 ? true : false;
	}
	
	protected static function ck_smallint($value){
		return $value >= -32768 && $value <= 32767 ? true : false;
	}
	
	protected static function ck_utinyint($value){
		return $value >= 0 && $value <= 255 ? true : false;
	}
	
	protected static function ck_tinyint($value){
		return $value >= -128 && $value <= 127 ? true : false;
	}
	
	protected static function ck_location($value){
		return preg_match('/^\d+\.\d{4,}$/', $value);
	}
	
	protected static function ck_empty($value){
		return empty($value);
	}
	
	protected static function ck_string($value){
		return true;
	}
	
	protected static function ck_mobile($value){
		return preg_match('/^1(3|4|5|7|8)[0-9]{9}$/', $value);;
	}
	
	protected static function ck_phone($value, $extra){
		if (preg_match('/^0[3-9]\d{2}\d{7,8}$/', $value)){
			return true;
		}elseif (preg_match('/^0(10|2\d)\d{7,8}$/', $value)){
			return true;
		}elseif (preg_match('/^[48]00\d{7}$/', $value)){
			return true;
		}elseif (preg_match('/^0085[23]\d{8}$/', $value)){	//HK	//Macau
			return true;
		}elseif (preg_match('/^00886\d{7,8}$/', $value)){	//TW
			return true;
		}else{
			if (isset($extra['short'])) {
				if (preg_match('/^1[012]\d{1,3}$/', $value)) {
					return true;
				}
				if (preg_match('/^9[56]\d{3,}$/', $value)) {
					return true;
				}
			}
			return false;
		}
	}
	
	protected static function ck_email($value){
		return strlen($value) >= 6 && preg_match('/^[\w\-\.]+@[\w\-\.]+(\.[\w\-]+)+$/', $value);
	}
	
	protected static function ck_url($value){
		return preg_match('/^https?:\/\/\w+(\.\w+){1,}/', $value);
	}
	
	protected static function ck_idcard($value){
		$len = strlen($value);
		switch($len){
			case 15:
				return preg_match('/^([0-9]){15}$/', $value);
			case 18:
				$warr = [7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2,1];
				$aarr = ['1','0','x','9','8','7','6','5','4','3','2'];
				$sum = 0;
				for($i=0; $i<17; $i++) {
					$sum += $value[$i] * $warr[$i];
				}
		
				$chk = $aarr[$sum % 11];
				return $chk == $value[17];
		}
		return false;
	}
	
	protected static function ck_password($value){
		$strlen = strlen(trim($value));
		return  $strlen >= 6 && $strlen <= 26;
	}
	
	protected static function ck_username($value){
		return preg_match('/^[a-z][a-z0-9\_]{4,20}$/i', $value);
	}
	
	protected static function ck_nickname($value){
		return preg_match('/^[A-za-z0-9\_\x{4e00}-\x{9fa5}]+$/u', $value);
	}
	
	protected static function ck_date($value){
		return ytimestamp($value);
	}
	
	protected static function ck_timestamp($value){
		return date('Y-m-d H:i:s', $value) !== fasle;
	}
	
	protected static function ck_bool($value){
		return $value == 1 || $value == 0;
	}
	
	protected static function ck_price($value){
		return preg_match('/^\d+(\.\d{2})?$/', $value);
	}
	
	//多个值
	protected static function ck_set($values, $extra, $func){
		foreach (explode(',', $values) as $value){
			if (!self::$func($value, $extra)) {
				return false;
			}
		}
		return true;
	}
	
	//区间
	protected static function ck_range($values, $extra, $func){
		list($min, $max) = explode(',', $values);
		if ( ($min == '' || self::$func($min, $extra)) && ($max == '' || self::$func($max, $extra))){
			if ($min != '' && $max !== '' && $min >= $max){
				return false;
			}
			return true;
		}else{
			return false;
		}
	}
	
	protected static function ck_array($values, $extra, $func){
		if (is_array($values)) {
			foreach ($values as $value){
				if (!self::$func($value)) {
					return false;
				}
			}
			return true;
		}else{
			return false;
		}
	}
	
	private static function _cn1_strlen($value){
		if (function_exists('mb_strlen')){
			return mb_strlen($value, CHARSET);
		}else{
			return count(preg_split("//u", $value, -1, PREG_SPLIT_NO_EMPTY));
		}
	}
	
	//将中文当作二个字符，只支持utf-8
	private static function _cn2_strlen($str){
		$count = 0;
		for($i = 0; $i < strlen($str); $i++){
			$value = ord($str[$i]);
			if($value > 127) {
				$count++;
				if($value >= 192 && $value <= 223) $i++;
				elseif($value >= 224 && $value <= 239) $i = $i + 2;
				elseif($value >= 240 && $value <= 247) $i = $i + 3;
			}
			$count++;
		}
		return $count;
	}
}