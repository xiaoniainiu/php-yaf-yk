<?php

/**
 *  Copyright (c) 2013-2014 yky@yky.pw 
 *  https://github.com/shukean/php-yaf-yk
 *  log.php 上午1:35:56 UTF-8
 */

namespace common;

class seccode{

	public $code;					//验证码字符
	public $width = 150;			//图片宽度
	public $height = 60;			//图片高度
	public $background = 0;			//随机背景图片
	public $adulterate = 1;			//随机背景图形
	public $ttf = 1;				//随机字体
	public $warping = 0;			//随机扭曲
	public $scatter = 0;			//随机打散，输入0-3，大于3时肉眼分辨难
	public $color = 0;				//背景随机颜色
	public $size = 0;				//字体随机大小
	public $shadow = 0;				//字符阴影

	public $datapath = '';			//背景图路径
	public $fontpath = '';			//字体路径
	public $fontcolor = array(		//字符颜色数组
		array(254, 101, 101),
		array(51, 143, 248),
		array(135, 68, 204)
	);

	private $_fontcolor = '';		//字体颜色
	private $_fontsize = 20;
	private $_im = null;			//图像句柄


	public function display(){
		$this->width = $this->width >= 60 && $this->width <= 360 ? $this->width : 150;
		$this->height = $this->height >= 20 && $this->height <= 120 ? $this->height : 60;
		$this->code = trim($this->code);
		if (function_exists('imagecreatetruecolor')) {
			$this->imgage();
		}else{
			$this->bitmap();
		}
	}

	private function imgage(){
		$bgdata = $this->init();
		$this->_im = imagecreatefromstring($bgdata);
		$this->adulterate && $this->adulterate();
		$this->ttf && $this->ttffont();
		$this->scatter && $this->scatter($this->_im);
		if(function_exists('imagepng')) {
			header('Content-type: image/png');
			imagepng($this->_im);
		} else {
			header('Content-type: image/jpeg');
			imagejpeg($this->_im, '', 100);
		}
		imagedestroy($this->_im);
	}


	private function init(){
		$this->_im = imagecreatetruecolor($this->width, $this->height);
		$backgrounds = $c = array();
		if ($this->background){
			if(false !== ($handle = @opendir($this->datapath.'background/'))) {
				while(false !== ($bgfile = @readdir($handle))) {
					if(preg_match('/\.jpg$/i', $bgfile)) {
						$backgrounds[] = $this->datapath.'background/'.$bgfile;
					}
				}
				@closedir($handle);
			}
			if($backgrounds) {
				$imwm = imagecreatefromjpeg($backgrounds[array_rand($backgrounds)]);
				$colorindex = imagecolorat($imwm, 0, 0);
				$c = imagecolorsforindex($imwm, $colorindex);
				$colorindex = imagecolorat($imwm, 1, 0);
				imagesetpixel($imwm, 0, 0, $colorindex);
				$c[0] = ($c['red'] + mt_rand(0, 256)) % 256; 
				$c[1] = ($c['green'] + mt_rand(0, 256)) % 256; 
				$c[2] = ($c['blue'] + mt_rand(0, 256)) % 256;
				imagecopymerge($this->_im, $imwm, 0, 0, mt_rand(0, 200 - $this->width), mt_rand(0, 80 - $this->height), imageSX($imwm), imageSY($imwm), 100);
				imagedestroy($imwm);
			}
		}else{
			$c = $this->fontcolor[array_rand($this->fontcolor, 1)];
			imagealphablending($this->_im, false);
			imagesavealpha($this->_im, true);
			$transparent = imagecolorallocatealpha($this->_im, 255, 255, 255, 127);
			imagefill($this->_im, 0, 0, $transparent);
		}
		ob_start();
		if(function_exists('imagepng')) {
			imagepng($this->_im);
		} else {
			imagejpeg($this->_im, '', 100);
		}
		imagedestroy($this->_im);
		$bgcontent = ob_get_contents();
		ob_end_clean();
		$this->_fontcolor = $c;
		return $bgcontent;
	}

	private function adulterate(){
		$A = mt_rand($this->height/8, $this->height/4);
		$T = mt_rand($this->width, $this->width * 2);
		$W = (2 * M_PI)/$T;
		$b = mt_rand(-$this->height/8, $this->height/8);
		$f = mt_rand(-$this->width/2, $this->width/2) * M_PI;
		
		$x1 = 0;
		$x2 = $this->width;
		$color = imagecolorallocate($this->_im, $this->_fontcolor[0], $this->_fontcolor[1], $this->_fontcolor[2]);
		
		for ($i = $x1; $i <= $x2; $i = $i + 0.9){
			if($W != 0){
				$point = $A * sin($W*$i + $f) + $b + $this->height/2;
				$j = 7;
				while ($j>0){
					imagesetpixel(
						$this->_im, 
						$i + ($j--), 
						$point + mt_rand(2, 4), 
						$color
					);
				}
			}
		}
	}

	private function ttffont(){
		$seccoderoot = $this->fontpath;
		$dirs = opendir($seccoderoot);
		$seccodettf = array();
		while(false !== ($entry = readdir($dirs))) {
			if($entry != '.' && $entry != '..' && in_array(strtolower(fileext($entry)), array('ttf', 'ttc'))) {
				$seccodettf[] = $entry;
			}
		}
		$size = $this->_fontsize;
		//对字符所在高宽进行计算
		$code_len = strlen($this->code);
		for ($i=0; $i<$code_len; $i++){
			$font[$i] = $seccoderoot.$seccodettf[array_rand($seccodettf)];
			$angle[$i] = mt_rand(-20, -5);
			list($lbx, $lby, $rbx, $rby, $rtx, $rty, $ltx, $lty) = 
				imagettfbbox($size, $angle[$i], $font[$i], $this->code[$i]);
			$width[$i] = abs(max($rtx, $rbx) - max($lbx, $ltx));
			$height[$i] = abs(max($lty, $rty) - min($lby, $rby));
		}
		
		$color = imagecolorallocatealpha($this->_im, $this->_fontcolor[0], $this->_fontcolor[1], $this->_fontcolor[2], 0);
		$x = abs($this->width - array_sum($width)) / 2;
		for ($i=0; $i<$code_len; $i++){
			$usable_y = $this->height - $height[$i];
			$y = $usable_y / 2 + $height[$i];
			imagettftext($this->_im, $size, $angle[$i], $x, $y, $color, $font[$i], $this->code[$i]);
			$x +=  $width[$i];
		}
		
		$this->warping && $this->warping($this->_im);
	}

	private function warping(&$obj) {
		$rgb = array();
		$direct = rand(0, 1);
		$width = imagesx($obj);
		$height = imagesy($obj);
		$level = $width / 20;
		for($j = 0;$j < $height;$j++) {
			for($i = 0;$i < $width;$i++) {
				$rgb[$i] = imagecolorat($obj, $i , $j);
			}
			for($i = 0;$i < $width;$i++) {
				$r = sin($j / $height * 2 * M_PI - M_PI * 0.5) * ($direct ? $level : -$level);
				imagesetpixel($obj, $i + $r , $j , $rgb[$i]);
			}
		}
	}

	private function scatter(&$obj, $level = 0) {
		$rgb = array();
		$this->scatter = $level ? $level : $this->scatter;
		$width = imagesx($obj);
		$height = imagesy($obj);
		for($j = 0;$j < $height;$j++) {
			for($i = 0;$i < $width;$i++) {
				$rgb[$i] = imagecolorat($obj, $i , $j);
			}
			for($i = 0;$i < $width;$i++) {
				$r = rand(-$this->scatter, $this->scatter);
				imagesetpixel($obj, $i + $r , $j , $rgb[$i]);
			}
		}
	}

	private function bitmap() {
		$numbers = array
		(
				'B' => array('00','fc','66','66','66','7c','66','66','fc','00'),
				'C' => array('00','38','64','c0','c0','c0','c4','64','3c','00'),
				'E' => array('00','fe','62','62','68','78','6a','62','fe','00'),
				'F' => array('00','f8','60','60','68','78','6a','62','fe','00'),
				'G' => array('00','78','cc','cc','de','c0','c4','c4','7c','00'),
				'H' => array('00','e7','66','66','66','7e','66','66','e7','00'),
				'J' => array('00','f8','cc','cc','cc','0c','0c','0c','7f','00'),
				'K' => array('00','f3','66','66','7c','78','6c','66','f7','00'),
				'M' => array('00','f7','63','6b','6b','77','77','77','e3','00'),
				'P' => array('00','f8','60','60','7c','66','66','66','fc','00'),
				'Q' => array('00','78','cc','cc','cc','cc','cc','cc','78','00'),
				'R' => array('00','f3','66','6c','7c','66','66','66','fc','00'),
				'T' => array('00','78','30','30','30','30','b4','b4','fc','00'),
				'V' => array('00','1c','1c','36','36','36','63','63','f7','00'),
				'W' => array('00','36','36','36','77','7f','6b','63','f7','00'),
				'X' => array('00','f7','66','3c','18','18','3c','66','ef','00'),
				'Y' => array('00','7e','18','18','18','3c','24','66','ef','00'),
				'2' => array('fc','c0','60','30','18','0c','cc','cc','78','00'),
				'3' => array('78','8c','0c','0c','38','0c','0c','8c','78','00'),
				'4' => array('00','3e','0c','fe','4c','6c','2c','3c','1c','1c'),
				'6' => array('78','cc','cc','cc','ec','d8','c0','60','3c','00'),
				'7' => array('30','30','38','18','18','18','1c','8c','fc','00'),
				'8' => array('78','cc','cc','cc','78','cc','cc','cc','78','00'),
				'9' => array('f0','18','0c','6c','dc','cc','cc','cc','78','00')
		);

		foreach($numbers as $i => $number) {
			for($j = 0; $j < 6; $j++) {
				$a1 = substr('012', mt_rand(0, 2), 1).substr('012345', mt_rand(0, 5), 1);
				$a2 = substr('012345', mt_rand(0, 5), 1).substr('0123', mt_rand(0, 3), 1);
				mt_rand(0, 1) == 1 ? array_push($numbers[$i], $a1) : array_unshift($numbers[$i], $a1);
				mt_rand(0, 1) == 0 ? array_push($numbers[$i], $a1) : array_unshift($numbers[$i], $a2);
			}
		}

		$bitmap = array();
		for($i = 0; $i < 20; $i++) {
			for($j = 0; $j <= 3; $j++) {
				$bytes = $numbers[$this->code[$j]][$i];
				$a = mt_rand(0, 14);
				array_push($bitmap, $bytes);
			}
		}

		for($i = 0; $i < 8; $i++) {
			$a = substr('012345', mt_rand(0, 2), 1) . substr('012345', mt_rand(0, 5), 1);
			array_unshift($bitmap, $a);
			array_push($bitmap, $a);
		}

		$image = pack('H*', '424d9e000000000000003e000000280000002000000018000000010001000000'.
				'0000600000000000000000000000000000000000000000000000FFFFFF00'.implode('', $bitmap));

		header('Content-Type: image/bmp');
		echo $image;
	}
}