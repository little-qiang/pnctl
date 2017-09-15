<?php

class Work {

	private $process_id     = 1;


	//设置进程数
	public function setProcessId($process_id){
		$this->process_id = $process_id;
	}

	public function runWorker($page){
		exec('/usr/bin/php /home/vagrant/Code/Test-09-dress/yii tools/check-cate '. $page);
	}

	
}
