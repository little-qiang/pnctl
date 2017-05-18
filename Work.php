<?php

class Work {

	public $username        = 'ak_test%d@gmail.com';
	public $password        = 'ak654321';
	public $crsf_filed      = 'securityToken';

	public $fileCookie      = './data/cookie_%d.txt';
	public $fileCart        = './data/cart.txt';
	public $fileTime        = './data/time.txt';
	public $fileLog	        = './data/log.txt';
	public $fileTrace	    = './data/trace.txt';

	public $urlLoginForm    = 'http://fairyseason.myefox.com/user/member/login';
	public $urlUserRegister = 'http://fairyseason.myefox.com/user/member/act_register';
	public $urlAddrRegister = 'http://fairyseason.myefox.com/user/address/ac_edit';
	public $urlLogin        = 'http://fairyseason.myefox.com/user/member/act_login';
	public $urlAddCart      = 'http://fairyseason.myefox.com/cart/ajax/add_to_cart';
	public $urlCreateOrder  = 'http://fairyseason.myefox.com/checkout/ajax/ajax_create_order';

	private $process_id     = 1;

	private $cartData       = [];


	public function __construct($cartData){
		$this->cartData   = $cartData;
	}

	//设置进程数
	public function setProcessId($process_id){
		$this->process_id = $process_id;
	}

	//封装的curl方法
	private function curl($url, $post = null, $withCookieFile = null, $saveCookieFile = null){
		$curl = curl_init();
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HEADER, 0);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	    if($saveCookieFile){
	        curl_setopt($curl, CURLOPT_COOKIEJAR, $saveCookieFile); //设置Cookie信息保存在指定的文件中
	    }
	    if($withCookieFile){
	        curl_setopt($curl, CURLOPT_COOKIEFILE, $withCookieFile); //读取cookie
	    }
	    if($post){
	    	curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));//要提交的信息
	    }
	    $rs = curl_exec($curl);
	    // file_put_contents($this->fileLog, $rs."\n", FILE_APPEND);
	    curl_close($curl);
	    return $rs;
	}

	//获取登录页面csrf令牌token, 目前服装站都是写死的token
	private function getToken(){
		return 'c063c4d327282264257d60163da3a33e';
		$login_content = $this->curl($this->urlLoginForm);
	    $reg = '#name="'.$this->crsf_filed.'" value="(?<token>.*)"#';
	    preg_match($reg, $login_content, $out);
	    return $out['token'] ?: '';
	}

	private function microtime_float(){
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	//设置用户登录cookie
	public function setCookie($flag = false){
		//如果保存的cookie超过1天，则重新获取cookie
		$cookieFile = sprintf($this->fileCookie, $this->process_id);
		if( $flag || !file_exists($cookieFile) || time() - filemtime($cookieFile) > 24*3600 || file_get_contents($cookieFile) == ''){
			$token = $this->getToken();
			//登录并保存cookie到本地
			$postLogin  = [
				'username'        => sprintf($this->username, $this->process_id),
				'password'        => $this->password,
				$this->crsf_filed => $token,
			];
			//保存cookie到本地
			$this->curl($this->urlLogin, $postLogin, null, $cookieFile);
		}
	}

	//随机加入购物车
	private function getRandCart(){
		$max = is_array($this->cartData) ? count($this->cartData) : 0;
		if($max == 0){
			$msg = 'cart.php have not goods data, can\'t add to cart';
			echo $msg;
			file_put_contents($this->fileTrace, $msg."\n", FILE_APPEND);
			exit;
		}
		$rand = $max == 1 ? 0 : mt_rand(0, $max - 1);
		return $this->cartData[$rand];
	}

	//计算 加入购物车|生成订单 的时间
	private function mathExecTime($url, $data = null){
		$cookieFile = sprintf($this->fileCookie, $this->process_id);
		$begin      = $this->microtime_float();
		$raw        = $this->curl($url, $data, $cookieFile);
		$end        = $this->microtime_float();

		file_put_contents($this->fileLog, $raw."\n", FILE_APPEND);
		if($rt = json_decode($raw, true) and $rt['error'] == 0){
			return [ 'cost_time' => $end - $begin, 'str_craw' => $raw ];
		} else {
			return [ 'cost_time' => -9.9999 ];
		}
	}

	//计算跳转支付时间
	private function mathPayTime($url){
		$begin = $this->microtime_float();
		$raw   = $this->curl($url);
		$end   = $this->microtime_float();
		file_put_contents($this->fileLog, $raw."\n", FILE_APPEND);
		return [ 'cost_time' => $end - $begin , 'str_craw' => $raw];
	}

	public function runWorker($times){
		for($i = 0; $i < $times; $i++){
			//计算加入购物车时间
			$strTime    = '';
			$this->setCookie();
			$cartData = $this->getRandCart();
			$cartRt = $this->mathExecTime($this->urlAddCart, $cartData);
			//生成订单的时间
			$orderData = [
				'consignee_readio' => 'on',
				'shipping'         => 7,
				'payment'          => 8
			];
			//跳转Paypal支付页面时间
			$payRt[' cost_time'] = -9.9999;
			$orderRt = $this->mathExecTime($this->urlCreateOrder, $orderData);
			if(isset($orderRt['str_craw'])){
				//正则匹配得到url
				preg_match('#action="(?<urlPaypal>.*)" m.*"#', str_replace("\\", "", $orderRt['str_craw']), $out);
				//得到的 paypal 支付 Url
				$urlPaypal = $out['urlPaypal'];
				file_put_contents($this->fileTrace, "url_paypal: ".$urlPaypal."\n", FILE_APPEND);
				$payRt = $this->mathPayTime($urlPaypal);
			}
			$strTime = sprintf("addCart time: %.4fs|createOrder time: %.4fs|jumpPay time: %.4fs\n",
				$cartRt['cost_time'], $orderRt['cost_time'], $payRt['cost_time']);
			//追加到日志，记录时间
			file_put_contents($this->fileTime, $strTime, FILE_APPEND);
		}
	}

	//自动注册一个用户，根据默认进程数，如进程为1，则注册为ak_test1@gmail.com,
	//可强制改变process_id, 然后注册为ak_test1000@gmail.com
	public function registerUser(){
		$cookieFile = sprintf($this->fileCookie, $this->process_id);
		$token = $this->getToken();
		$postData = [
			'email'        	  => sprintf($this->username, $this->process_id),
			'password'        => $this->password,
			'comfirmpass'     => $this->password,
			$this->crsf_filed => $token,
			'agreement'       => 1,
			'agreesubcribe'   => 1,
		];
		return $this->curl($this->urlUserRegister, $postData, null, $cookieFile);
	}

	public function checkUserExist($str){
		preg_match('#already exists#', $str, $out);
		return (bool)count($out);
	}

	//自动注册一个地址
	public function registerAddr(){
		$cookieFile = sprintf($this->fileCookie, $this->process_id);
		$postData = [
			'email'           => sprintf($this->username, $this->process_id),
			'first_name'      => sprintf('AA_%d', $this->process_id),
			'last_name'       => sprintf('KK_%d', $this->process_id),
			'address_string'  => sprintf('ADDR_%d', $this->process_id),
			'country'         => sprintf(220),
			'province_string' => sprintf('State_%d', $this->process_id),
			'city_string'     => sprintf('City_%d', $this->process_id),
			'zipcode'         => sprintf('110%d', $this->process_id),
			'tel'             => sprintf('1771234%s', str_pad($this->process_id, 4, "0", STR_PAD_LEFT)),
		];
		$bool = $this->curl($this->urlAddrRegister, $postData, $cookieFile);
		return $bool;
	}
}
