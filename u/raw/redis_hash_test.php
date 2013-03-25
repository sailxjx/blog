<?php

global $ts;
$ts = microtime(true);
$oRedis = new Redis();
$oRedis->connect('127.0.0.1', 6379);
mOutput('connected');
//multi begin
$sMKey = 'rh_multi';
$iMax = 200000;
for ($i = 0; $i < $iMax; $i++) {
	$oRedis->hset($sMKey, 'email' . $i, $i);
}
mOutput('multi hset finish');
for ($i = 0; $i < $iMax; $i++) {
	$ir = rand(0, $iMax - 1);
	$oRedis->hget($sMKey, 'email' . $ir);
}
mOutput('multi hget finish');
//single begin
$sSKey = 'rh_single';
for ($i = 0; $i < $iMax; $i++) {
	$oRedis->hset($sSKey, 'email', $i);
}
mOutput('single hset finish');
for ($i = 0; $i < $iMax; $i++) {
	$ir = rand(0, $iMax - 1);
	$oRedis->hget($sSKey, 'email');
}
mOutput('single hget finish');
//hash key begin
$sHKey = 'rh_hash';
for ($i = 0; $i < $iMax; $i++) {
	$oRedis->hset($sHKey . substr(md5($i), 0, 2), 'email' . $i, $i);
}
mOutput('hashkey hset finish');
for ($i = 0; $i < $iMax; $i++) {
	$ir = rand(0, $iMax - 1);
	$oRedis->hget($sHKey . substr(md5($ir), 0, 2), 'email' . $ir);
}
mOutput('hashkey hget finish');

function mOutput($str = '') {
	global $ts;
	echo $str, PHP_EOL;
	echo 'duration: ', round(microtime(true) - $ts, 3), PHP_EOL;
	$ts = microtime(true);
}

exit(0);