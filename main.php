<?php

require "./Task.php";
require "./Work.php";

$num_count   = isset($argv[1]) ? $argv[1] : 1;
$num_process = isset($argv[2]) ? $argv[2] : 1;

$allCartData = require('./data/cart.php');

$start = microtime(true);

$work = new Work($allCartData);
if( 0 && extension_loaded('pcntl')){
	$task = new Task($work, $num_process);
	$task->run($num_count);
} else {
    $work->setProcessId($num_process);
    $cookieFile = sprintf('data/cookie_%d.txt', $num_process);
    if(!file_exists($cookieFile) || empty(file_get_contents($cookieFile))){
        $rawRegister = $work->registerUser();
        //检测如果用户存在，则更新cookie
        $work->checkUserExist($rawRegister) and $work->setCookie(true);
        $work->registerAddr();
    }
	$work->runWorker($num_count);
}
$end = microtime(true);

printf("cost %.2fs\n", $end-$start);


