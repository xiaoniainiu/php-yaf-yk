<?php


echo <<<EOT
<br><br>
这个文件只是用来演示服务器的配置<br><br>
http://yaf.laruence.com/manual/tutorial.firstpage.html#tutorial.rewrite<br>
php.ini中需要设置<br>
yaf.use_namespace = 1<br>
yaf.use_spl_autoload=1<br>
可以具体将各个网站转移到各自对应的入口文件<br><br>

<br>-application 		应用目录
<br>---adminx			adminx应用
<br>-----controllers	应用默认的控制器
<br>-----library		应用库
<br>-----models			应用数据库
<br>-----modules		应用的其它扩展控制器
<br>-----plugins		应用的插件
<br>-----views			应用的模版
<br>-------default		应用的模版的deault风格
<br>-----Bootstrap.php	应用的启动文件
<br>-conf				配置
<br>-data				额外的数rnd
<br>-models				数据库,其它的数据库可继承本类中的
<br>-public				入口

EOT;

