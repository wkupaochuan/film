<?php

class Lol_dytt extends MY_Controller {
	public function test($url){
		$this->_craw_film_detail("http://www.loldytt.com/Juqingdianying/{$url}/");
	}

	public function re_craw_loldytt($data_path, $output_path)
	{
		$start_time = time();
		$data_path = str_replace(':', '/', $data_path);
		$output_path = str_replace(':', '/', $output_path);
		$rfp = fopen($data_path, 'r');
		$wfp = fopen($output_path, 'w');
		$this->load->model('Film_model');
		$this->load->model('Film_bt_model');

		$i = 0;
		while(!feof($rfp)) {
			if($i++ > 100000) {
				break;
			}

			$line = trim(fgets($rfp));
			if(empty($line)) {
				continue;
			}

			$data = json_decode($line, true);
			if(empty($data) || !is_array($data)) {
				$this->log_error('空:' . $line);
			}

			if($data['loldytt_url']){
				$film_info = $this->_craw_film_detail($data['loldytt_url']);
				if(empty($film_info) || empty($film_info['name'])){
					$this->log_error('get nothing from url ' . $data['loldytt_url']);
				}else{
					$film_info['url'] = $data['loldytt_url'];
					fputs($wfp, json_encode($film_info) . PHP_EOL);
					$this->c_echo('success ' . $i . ':' . $data['loldytt_url']);
				}
			}
		}

		fclose($rfp);
		fclose($wfp);
		echo "end. cost " . (time() - $start_time) . PHP_EOL;
	}

	/************************************************* private methods *************************************************************/

	private function _craw_film_detail($url){
		$ret = array();

		if(empty($url)){
			return $ret;
		}

		$html = $this->_get_film_html($url);

		if(empty($html)) {
			$this->log_error('get no html from ' . $url);
			return $ret;
		}

		// 名称
		$pattern = '#<div class="biaoti">([\s\S]*)</div>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$tmp_html = $matches[1];
			$pattern = '#<a href="[a-zA-Z/]+">([\s\S]*)</a>#U';
			$matches = array();
			preg_match($pattern, $tmp_html, $matches);
			if(!empty($matches) && !empty($matches[1])) {
				$ret['name'] = $matches[1];
			}
		}

		// 导演
		$pattern = '#<p>导演:([\s\S]*)<br>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$ret['director'] = trim($matches[1]);
		}

		// 主演
		$pattern = '#<li>主　演([\s\S]*)</li>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$tmp = explode('：', $matches[1]);
			if(count($tmp) > 1){
				$ret['actors'] = array();
				$actors = explode(' ', $tmp[1]);
				foreach($actors as $actor){
					if(!empty(trim($actor))){
						array_push($ret['actors'], trim($actor));
					}
				}
			}
		}

		// 相关推荐
		$pattern = '#<div class="tu">([\s\S]*)</div>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$tmp_html = $matches[1];
			$pattern = '#<a href="([\s\S]*)">#U';
			$matches = array();
			preg_match_all($pattern, $tmp_html, $matches);
			if(!empty($matches) && !empty($matches[1])) {
				$recom = array();
				foreach($matches[1] as $tmp){
					if(!empty($tmp)){
						$path = substr($tmp, strpos($tmp, 'com') + 4);
						$path = trim($path, '/');
						array_push($recom, $path);
					}
				}
				if(!empty($recom)){
					$ret['recom'] = $recom;
				}
			}
		}

		// 迅雷资源&magnet资源
		$thunders = $this->_get_film_thunder_links($html);
		if(!empty($thunders)){
			$ret = array_merge($ret, $thunders);
		}

		// bt资源
		$bts = $this->_get_film_BT_links($html);
		if(!empty($bts)){
			$ret['bt'] = $bts;
		}

		return $ret;
	}

	/**
	 * 根据url获取Html(有反爬取机制, 需要根据返回的内容拼接url然后进行多次递归组装, 直到返回争取的html)
	 * @param $encryptUrl
	 * @param string $param
	 * @param int $level
	 * @return string
	 */
	private function _get_film_html($encryptUrl, $param = '', $level = 0) {
		$res = '';

		if(empty($encryptUrl) || $level >= 250) {
			return $res;
		}

		$html = $param == ''? $this->_curl($encryptUrl):$this->_curl($encryptUrl . '?' . $param);
		if(strlen($html) > 500) {
			$res = iconv(mb_detect_encoding($html,array('UTF-8','GBK','GB2312')), 'UTF-8', $html);
			return $res;
		}

		$pattern = '#location([\s\S]*)</scri#';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$param = $matches[1];
			$tmp = explode('?', $param);
			$param = $tmp[1];
			$param = str_replace(array('"', '+', ';', ' '), '', $param);

			return $this->_get_film_html($encryptUrl, $param, $level + 1);
		}else{
			$errorMsg = __FUNCTION__ . ':' . __LINE__ . ':' . 'no matches.' . $html;
			$this->log_error($errorMsg);
			return '';
		}
	}

	/**
	 * 从电影详情页提取迅雷下载链接
	 * @param $html
	 * @return array [{'title', 'link'}];
	 */
	private function _get_film_thunder_links($html){
		$ret = array(
			'thunder' => array(),
			'magnet' => array(),
		);

		if(empty($html)) {
			return $ret;
		}

		$pattern = '#<div id="[a-z]?jishu">([\s\S]*)</ul>#U';
		$matches = array();
		preg_match_all($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			foreach($matches[1] as $html){
				$linkPattern = '#href="([\s\S]*)"#U';
				$titlePattern = '#title="([\s\S]*)"#U';
				$linkMatches = array();
				$titleMatches = array();
				preg_match_all($linkPattern, $html, $linkMatches);
				preg_match_all($titlePattern, $html, $titleMatches);
				if(!empty($linkMatches) && !empty($linkMatches[1]) && !empty($titleMatches) && !empty($titleMatches[1]) && count($linkMatches[1]) === count($titleMatches[1]) ) {
					$bt = array();
					for($i = 0; $i < count($linkMatches[1]); ++$i) {
						$bt[] = array(
							'title' => $titleMatches[1][$i],
							'link' => $linkMatches[1][$i],
						);
					}

					if(strpos($bt[0]['link'], 'thunder:') !== false){
						array_push($ret['thunder'], $bt);
					}else if(strpos($bt[0]['link'], 'magnet:') !== false){
						array_push($ret['magnet'], $bt);
					}
				}
			}
		}

		return $ret;
	}

	/**
	 * 从电影详情页提取bt下载链接
	 * @param $html
	 * @return array
	 */
	private function _get_film_BT_links($html){
		$ret = array();

		if(empty($html)) {
			return $ret;
		}

		$pattern = '#<div id="bt">([\s\S]*)</ul>#U';
		$matches = array();
		preg_match_all($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			foreach($matches[1] as $html){
				$linkPattern = '#href="([\s\S]*)"#U';
				$titlePattern = '#title="([\s\S]*)"#U';
				$linkMatches = array();
				$titleMatches = array();
				preg_match_all($linkPattern, $html, $linkMatches);
				preg_match_all($titlePattern, $html, $titleMatches);
				if(!empty($linkMatches) && !empty($linkMatches[1]) && !empty($titleMatches) && !empty($titleMatches[1]) && count($linkMatches[1]) === count($titleMatches[1]) ) {
					$tmp = array();
					for($i = 0; $i < count($linkMatches[1]); $i++) {
						$tmp[] = array(
							'title' => $titleMatches[1][$i],
							'link' => $linkMatches[1][$i],
						);
					}
					if(!empty($tmp)){
						array_push($ret, $tmp);
					}
				}
			}
		}

		return $ret;
	}

	private function c_echo($str)  {
		echo $str . PHP_EOL;
	}

	private function log_error($msg, $function = 0, $line = 0){
		$this->c_echo('user error :on function ' . $function . ' line ' . $line . ':' . $msg);
	}

	private function _curl($url, $post_data = array(), $cookie_jar = '', $header = array()){
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
		if(!empty($proxy)){
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

		$res  = curl_exec($ch);
		$errno     = curl_errno($ch);
		if($errno != 0){
//			echo 'retry 1' . PHP_EOL;
			$res  = curl_exec($ch);
			$errno     = curl_errno($ch);
			if($errno != 0){
//				echo 'retry 2' . PHP_EOL;
				$res  = curl_exec($ch);
				$errno     = curl_errno($ch);
			}
		}
		$http_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errmsg    = (0 != $errno) ? curl_error($ch) : '';
		curl_close($ch);

//		echo $http_code . PHP_EOL. 'errno:' . $errno . PHP_EOL . $errmsg . PHP_EOL . $res;exit;
		if($http_code != 200 || $errno != 0) {
			$this->log_error('curl fail.http code:' . $http_code .';errno:' .  $errno . ';errmsg:' . $errmsg . ';url:' . $url);
			return '';
		}else{
			return $res;
		}
	}
}
