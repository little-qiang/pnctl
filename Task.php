<?php

/**

 * 多进程任务处理辅助类

 */
class Task{

	//worker进程最大数量, 至少两个
	protected $maxProcess;
 	//任务的实际处理者，对象, 必须有 runWorker 方法
	protected $worker;


	public function __construct($worker, $maxProcess = 4) {
		$this->worker = $worker;
		$this->maxProcess = max(2, (int)$maxProcess);
	}

	/**
	 * fork子进程处理数据
	 * @param Array $data 需要处理的数据，必须是数组
	 */
	public function run($count) {
		// 每个进程处理的次数
		$n = floor($count / $this->maxProcess);
		$childs = array();

		for($i = $count, $j = 1; $i > 0; $i -= $n, $j++) {
			$times = $i < $n ? $i : $n;
			$mod = $j % $this->maxProcess + 1;
			$pid = pcntl_fork();
			if($pid == -1) {
				echo "Fork worker failed!";
				return false;
			} elseif($pid) {
				echo "Fork worker success! pid:",  $pid, "\n";
				$childs[] = $pid;
			} else {
				cli_set_process_title('php_pcntl_'.$mod);
				$times = $i < $n ? $i : $n;
				$this->worker->setProcessId($mod);
				$this->worker->runWorker($times);
				exit();
			}
		}
		$this->check($childs);
	}

	//检测子进程状态，监控子进程是否退出，并防止僵尸进程
	protected function check($childs) {
		while(true) {
			foreach($childs as $index => $pid) {
				$pid && $res = pcntl_waitpid($pid, $status, WNOHANG);
				if(!$pid || $res == -1) {
					echo "End worker: $pid \n";
					unset($childs[$index]);
				}
			}
			if(empty($childs)) break;
			sleep(1);
		}
	}
}
