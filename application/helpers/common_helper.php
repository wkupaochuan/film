<?php

function f_echo($str)  {
	echo $str . PHP_EOL;
}

function f_log_error($msg, $function = 0, $line = 0){
	if(empty($function)){
		f_echo('user error :' . $msg);
	}else{
		f_echo('user error :on function ' . $function . ' line ' . $line . ':' . $msg);
	}
}

function f_curl($url, $post_data = array(), $cookie_jar = '', $header = array(), $retry_times = 3, $user_proxy = true){
	$proxy = array();
	$proxy_array = array(
		array(
			'ip' => '111.13.7.119',
			'port' => '8080',
		),
		array(
			'ip' => '111.13.2.131',
			'port' => '80',
		),
	);
	if(($rand = rand(0, count($proxy_array))) < count($proxy_array)){
		$proxy = $proxy_array[$rand];
	}

	$ch = curl_init();
	if($user_proxy && !empty($proxy)){
		curl_setopt($ch,CURLOPT_PROXY, $proxy['ip']);
		curl_setopt($ch,CURLOPT_PROXYPORT, $proxy['port']);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	if(!empty($post_data)) {
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	}
	if(!empty($cookie_jar)) {
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
	}
	if(!empty($header)){
		curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
	}

	$res = '';
	while($retry_times-- > 0){
		$res  = curl_exec($ch);
		$errno     = curl_errno($ch);
		$http_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errmsg    = (0 != $errno) ? curl_error($ch) : '';
		if($errno == 0){
			break;
		}

		// 7(无法连接到主机), 28(连接超时)
		if($errno == 7 || $errno == 28){
			sleep(1);
		}
	}
	curl_close($ch);

	if(strlen($res) < 50 || $errno != 0) {
		f_log_error('curl fail.http code:' . $http_code .';errno:' .  $errno . ';errmsg:' . $errmsg . ';url:' . $url);
		return '';
	}else{
		return $res;
	}
}

