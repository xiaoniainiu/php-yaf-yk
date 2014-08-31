<?php

define('PATH', dirname(dirname(__FILE__)));

function set_timeoffset($offset = 0){
	date_default_timezone_set('Etc/GMT'.($offset > 0 ? '-' : '+' ).abs($offset));
}

set_timeoffset(8);

if (!empty($argv)){
	
	$app = null;
	switch ($argc){
		case 3:
			$app = $argv[2];
		case 2:
			$table = $argv[1];
			break;
		case 1:
			echo "\$1 table name, $2 app name", PHP_EOL; exit;
			break;
	}
	
}else{
	exit('only support cli mode');
}

if (strpos($table, '_')){
	$path = explode('_', $table);
	$file = array_pop($path);
	$dir = implode('/', $path);
	@mkdir(PATH.'/models/'.$dir, 0755, true);
	
	mkfile('\yk\mysql\database', $table, PATH.'/models/'.$dir.'/'.$file.'.php', false);
	
	if ($app){
		@mkdir(PATH."/application/$app/models/".$dir, 0755, true);
		mkfile('\models\\'.$table, $table, PATH."/application/$app/models/".$dir.'/'.$file.'.php', $app);
	}
	
}else{
	
	mkfile('\yk\mysql\database', $table, PATH.'/models/'.$table.'.php', false);
	
	if ($app){
		mkfile('\models\\'.$table, $table, PATH."$app/application/models/".$table.'.php', $app);
	}
}


function mkfile($extend, $tablename, $file, $isapp){

$time = date('Y-m-d H:i:s');

$namespace = $isapp ? $isapp."\\" : '';

$DOC = <<<EOT
<?php

/**
 *  Copyright (c) 2013-2014 
 *  This is no free page
 *  $tablename.php  $time  UTF-8
 *  @author yky@yky.pw
 */

namespace {$namespace}models;

use \yk\mysql\instance;

class $tablename extends $extend{
EOT;

if (!$isapp){
	
$DOC .= <<<EOT

	public function __construct(){
		\$this->_primary_key = 'id';
	}
	
	public function table(){
		return '$tablename';
	}
	
	public function ins(\$data){
		return instance::insert(\$this->write(), \$this->table(), \$data, true);
	}
EOT;

}

$DOC .= '
	
}';


if (file_exists($file)){
	echo $file, " exist", PHP_EOL;
}else{
	
	file_put_contents($file, $DOC);
}

}

