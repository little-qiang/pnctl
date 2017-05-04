<?php

class Work {

	public $username        = 'ak_test%d@gmail.com';
	public $password        = 'ak654321';
	public $crsf_filed      = 'securityToken';

	public $fileCookie      = './data/cookie_%d.txt';
	public $fileCart        = './data/cart.txt';
	public $fileTime        = './data/time.txt';
	public $fileLog	        = './data/log.txt';

	public $urlLoginForm    = 'http://fairyseason.myefox.com/user/member/login';
	public $urlUserRegister = 'http://fairyseason.myefox.com/user/member/act_register';
	public $urlAddrRegister = 'http://fairyseason.myefox.com/user/address/ac_edit';
	public $urlLogin        = 'http://fairyseason.myefox.com/user/member/act_login';
	public $urlAddCart      = 'http://fairyseason.myefox.com/cart/ajax/add_to_cart';
	public $urlCreateOrder  = 'http://fairyseason.myefox.com/checkout/ajax/ajax_create_order';

	private $process_id     = 1;

	public $cartData        = [];


	public function __construct($cartData){
		$this->cartData   = $cartData;
	}

	public function setProcessId($process_id){
		$this->process_id = $process_id;
	}

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

	//获取登录页面csrf令牌token
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

	private function checkCookie($flag = false){
		//如果保存的cookie超过1天，则重新获取cookie
		$cookieFile = sprintf($this->fileCookie, $this->process_id);
		if( $flag || file_exists($cookieFile) ||time() - filemtime($cookieFile) > 24*3600 || file_get_contents($cookieFile) == ''){
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

	private function getRandCart(){
		$rand = mt_rand(0, count($this->cartData));
		return $this->cartData[$rand];
	}

	private function mathExecTime($url, $data){
		$cookieFile = sprintf($this->fileCookie, $this->process_id);
		$begin      = $this->microtime_float();
		$raw        = $this->curl($url, $data, $cookieFile);
		$end        = $this->microtime_float();
		return ( $rt = json_decode($raw, true) and $rt['error'] == 0 ) ? $end - $begin : -9.9999;
	}

	public function runWorker($times){
		for($i = 0; $i < $times; $i++){
			$strTime    = '';
			$this->checkCookie();
			$cartData = $this->getRandCart();
			$cartTime = $this->mathExecTime($this->urlAddCart, $cartData);

			$orderData = [
				'consignee_readio' => 'on',
				'shipping'         => 7,
				'payment'          => 8
			];
			$orderTime = $this->mathExecTime($this->urlCreateOrder, $orderData);
			$strTime = sprintf("addCart time: %.4fs|createOrder time: %.4fs\n", $cartTime, $orderTime);
			file_put_contents($this->fileTime, $strTime, FILE_APPEND);
		}
	}

	public function regiserUser(){
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
		$this->curl($this->urlUserRegister, $postData, null, $cookieFile);
	}

	public function regiserAddr(){
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
		var_dump($bool);
	}
}
