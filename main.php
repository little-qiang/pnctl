<?php

require "./Task.php";
require "./Work.php";

$num_count   = isset($argv[1]) ? $argv[1] : 100;
$num_process = isset($argv[2]) ? $argv[2] : 4;

$allCartData = require('./data/cart.php');

$start = microtime(true);
$work = new Work($allCartData);
$task = new Task($work, $num_process);
$task->run($num_count);
$end = microtime(true);

printf('cost %.2fs', $end-$start);


