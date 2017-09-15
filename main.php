<?php

require "./Task.php";
require "./Work.php";

$work = new Work();
$task = new Task($work, 61);
$task->run(122000);


