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

function f_curl($url, $post_data = array(), $cookie_jar = '', $header = array(), $retry_times = 1, $user_proxy = true){
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
			f_log_error('curl fail.errno:' . $errno . ', and we are retrying');
//			sleep(rand(1,5));
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

function w_str_split($str){
	$ret = array();
	$encoding = mb_detect_encoding($str);
	for($i = 0; $i < mb_strlen($str, $encoding); $i++){
		$ret[] = mb_substr($str, $i, 1, $encoding);
	}

	return $ret;
}

function explode_by_num($str){
	$ret = array();
	$arr_str = w_str_split($str);
	$counter = 0;
	$ret[$counter] = $arr_str[0];
	$numeric = is_numeric($arr_str[0]);
	for($i = 1; $i < count($arr_str); $i++){
		$char = $arr_str[$i];
		if(is_numeric($char) === $numeric){
			$ret[$counter] .= $char;
		}else{
			$counter++;
			$ret[$counter] = $char;
			$numeric = is_numeric($char);
		}
	}

	return $ret;
}

/**
 * 判定请求是否来自移动端
 * @return bool
 */
function is_mobile()
{
	if(!isset($_SERVER['HTTP_USER_AGENT'])){
		return false;
	}
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	return strpos($agent, 'iphone') ||  strpos($agent, 'android') || strpos($agent, 'ipad');
}

/**
 * 判定请求是否来自爬虫
 * @return bool
 */
function from_robot()
{
	if(!isset($_SERVER['HTTP_USER_AGENT'])){
		return false;
	}
	$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
	return strpos($agent, 'Spider') ||  strpos($agent, 'Googlebot') || strpos($agent, 'MJ12bot') || strpos($agent, 'Baiduspider');
}
