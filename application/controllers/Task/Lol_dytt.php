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
		$wfp = fopen($output_path, 'a+');
		$i = 0;
		while(!feof($rfp)) {
			echo $i . PHP_EOL;
			if($i++ > 100000) {
				break;
			}

			$line = trim(fgets($rfp));
			if(empty($line)) {
				continue;
			}

			if($i < 173){
				continue;
			}

			$data = json_decode($line, true);
			if(empty($data) || !is_array($data)) {
				$this->log_error('空:' . $line);
			}

			$url = trim(substr($data['url'], strpos($data['url'], '.com') + 4), '/');

			$this->_craw_and_store($url, $wfp, $data);
		}

		fclose($rfp);
		fclose($wfp);
		echo "end. cost " . (time() - $start_time) . PHP_EOL;
	}

	public function craw_films_by_recom($output_path){
		$start_time = time();
		$output_path = str_replace(':', '/', $output_path);
		$wfp = fopen($output_path, 'a+');
		$page = 0;
		$limit = 30;
		$this->load->model('Film_model');
		$this->load->model('Lol_recom_model');
		while($page++ < 10000000){
			$un_crawed_urls = $this->Lol_recom_model->get_un_crawed_urls(0, $limit);

			if(empty($un_crawed_urls)){
				echo 'end ' . $page . PHP_EOL;
				break;
			}

			foreach($un_crawed_urls as $tmp){
				$lol_url = $tmp['lol_url'];
				echo 'no exist:' . $lol_url . PHP_EOL;
				if($this->_craw_and_store($lol_url, $wfp)){
					echo 'success:' . $lol_url . PHP_EOL;
				}else{
					$this->Lol_recom_model->incr_invalid_times($lol_url);
					echo 'fail:' . $lol_url . PHP_EOL;
				}
			}
		}

		fclose($wfp);
		echo "end. cost " . (time() - $start_time) . PHP_EOL;
	}

	/************************************************* private methods *************************************************************/

	/**
	 * 爬取存储lol film
	 * @param $url
	 * @param $wfp
	 * @param array $film_detail
	 * @return bool
	 */
	private function _craw_and_store($url, $wfp, $film_detail = array()){
		if(empty($film_detail)){
			$film_detail = $this->_craw_film_detail($url);
		}

		if(empty($film_detail)){
			$this->log_error('craw nothing from url ' . $url);
			return false;
		}else if(!empty($film_detail)){
			$this->load->model('Lol_recom_model');
			$this->load->model('Film_bt_model');
			$this->load->model('Film_bt_batch_model');

			// add recoms
			if(!empty($film_detail['recom'])){
				$this->_store_recom($url, $film_detail['recom']);
			}

			// find film from db
			if(empty($film_detail['actors'])){
				// output > file
				$film_detail['no_actors'] = 1;
				$film_detail['url'] = $url;
				fputs($wfp, json_encode($film_detail) . PHP_EOL);
				return false;
			}else{
				$query_res = $this->_search_unique_film_from_db($url, $film_detail['name'], $film_detail['actors'], empty($film_detail['director'])? '':$film_detail['director'], empty($film_detail['year'])? '':$film_detail['year']);

				if($query_res['count'] == 0 || $query_res['count'] > 1){
					$film_detail['query_count'] = $query_res['count'];
					// output > file
					fputs($wfp, json_encode($film_detail) . PHP_EOL);
					$this->log_error('search result fail');
					return false;
				}else{
					$db_film_detail = $query_res['film_detail'];
					if(empty($db_film_detail) || empty($db_film_detail['douban_id'])){
						$this->log_error('bad db film' . json_encode($db_film_detail));
						return false;
					}

					// update lol url
					if(empty($db_film_detail['lol_url'])){
						$this->Film_model->update_by_douban_id($db_film_detail['douban_id'], array(
							'lol_url' => $url,
							'download_able' => 1,
						));
					}else if($db_film_detail['lol_url'] != $url){
						// 错误
						$film_detail['query_count'] = $query_res['count'];
						$film_detail['multi'] = 1;
						// output > file
						fputs($wfp, json_encode($film_detail) . PHP_EOL);
						$this->log_error('duplicate resources');
						return false;
					}

					// process bts
					$this->_store_bts($film_detail, $db_film_detail['douban_id']);
				}
			}
		}

		return true;
	}

	/**
	 * 存储lol recom
	 * @param $lol_url
	 * @param $recom_url_arr
	 */
	private function _store_recom($lol_url, $recom_url_arr){
		$recom_data_array = array();
		foreach($recom_url_arr as $tmp){
			array_push(
				$recom_data_array,
				array(
					'lol_url' => $lol_url,
					'recom_url' => $tmp,
				)
			);
		}

		if(!empty($recom_data_array)){
			$this->Lol_recom_model->insert_batch($recom_data_array);
		}
	}

	/**
	 * 存储bts
	 * @param $film_detail
	 * @param $douban_id
	 */
	private function _store_bts($film_detail, $douban_id){
		$types = array(
			'thunder' => 1,
			'bt' => 2,
			'magnet' => 3
		);
		$insert_data = array();
		foreach($types as $type_key => $type){
			if(!empty($film_detail[$type_key])){
				foreach($film_detail[$type_key] as $bt_array){
					$exist_bts = $this->Film_bt_model->get_by_urls(array_column($bt_array, 'link'));
					$exist_urls = array();

					if(empty($exist_bts)){
						$batch_id = $this->Film_bt_batch_model->insert(array('type' => $type));
					}else{
						$exist_urls = array_column($exist_bts, 'url');
						$batch_id = $exist_bts[0]['batch_id'];
					}

					foreach($bt_array as $bt){
						if(!in_array($bt['link'], $exist_urls)){
							array_push($insert_data, array(
								'batch_id' => $batch_id,
								'douban_id' => $douban_id,
								'url' => $bt['link'],
								'name' => $bt['title'],
							));
						}
					}
				}
			}
		}

		if(!empty($insert_data)){
			// insert bts
			$this->Film_bt_model->insert_batch($insert_data);
		}
	}

	/**
	 * 爬取详情
	 * @param $url
	 * @return array
	 */
	private function _craw_film_detail($url){
		$short_url = $url;
		$ret = array();

		if(empty($url)){
			return $ret;
		}
		$url = "http://www.loldytt.com/{$url}/";

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
			$pattern = '#<a href="([\s\S]*)">([\s\S]*)</a>#U';
			$matches = array();
			preg_match($pattern, $tmp_html, $matches);
			if(!empty($matches) && !empty($matches[2])) {
				$ret['name'] = $matches[2];
			}
		}

		if(empty($ret['name'])){
			return $ret;
		}

		// 导演
		$pattern = '#<p>导演:([\s\S]*)<br>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$ret['director'] = trim($matches[1]);
		}

		// 时间
		$pattern = '#<br>上映日期:([\s\S]*)<br>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			$part_html = $matches[1];
			$pattern = '#([\d]{4}-[\d]{2}-[\d]{2})#U';
			$matches = array();
			preg_match_all($pattern, $part_html, $matches);
			if(!empty($matches) && !empty($matches[1])) {
				$full_time = $matches[1][count($matches[1]) - 1];
				$ret['year'] = date('Y', strtotime($full_time));
			}
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

		if(empty($ret['thunder']) && empty($ret['bt']) && empty($ret['magnet'])){
			return array();
		}

		$ret['url'] = $short_url;
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

		$cookie_file_path = './lol_cookie.txt';
		static $cookie_time;
		if(empty($cookie_time) || (time() - $cookie_time) > 3){
			$cookie_time = time();
			file_put_contents($cookie_file_path, '');
		}

		$html = $param == ''? $this->_curl($encryptUrl, array(), $cookie_file_path):$this->_curl($encryptUrl . '?' . $param, array(), $cookie_file_path);
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

	/**
	 * 查询唯一的电影详情
	 * @param $lol_url
	 * @param $name
	 * @param $actors array
	 * @param $director string
	 * @return array
	 */
	private function _search_unique_film_from_db($lol_url, $name, $actors, $director, $year){
		$res = array(
			'count' => 0,
			'film_detail' => array()
		);

		$this->load->Model('Film_name_model');
		$this->load->Model('Film_model');

		// 已经存在bt资源的
		$query_res = $this->Film_model->query_by_lol_url($lol_url);
		if(!empty($query_res)){
			$res = array(
				'count' => 1,
				'film_detail' => $query_res
			);

			return $res;
		}

//		if(strpos($name, '(') !== false){
//			$name = substr($name, 0, stripos($name, '('));
//		}

		$query_res = $this->Film_name_model->search_by_name($name);

		// '生化危机5终章' =>  '生化危机5'
		if(empty($query_res)) {
			$pattern = '#[\s\S]*[\d]#U';
			preg_match($pattern, $name, $matches);
			if(!empty($matches) && !empty($matches[0])){
				$query_res = $this->Film_name_model->search_by_name($matches[0]);
			}
		}

		// 惊悚救援/核力突破
		if(empty($query_res) && strpos($name, '/') !== false){
			$name1 = substr($name, 0, stripos($name, '/'));
			$name2 = substr($name, stripos($name, '/') + 1);
			$query_res = array_merge($this->Film_name_model->search_by_name($name1) , $this->Film_name_model->search_by_name($name2));
		}

		// "假小子2016" => "假小子" 这种情况
		if(empty($query_res)){
			$pattern = '#[\d]*$#';
			$query_res = $this->Film_name_model->search_by_name(preg_replace($pattern, '', $name));
		}

		// 黄飞鸿合集=>黄飞鸿
		if(empty($query_res) && strpos($name, '合集') !== false){
			$query_res = $this->Film_name_model->search_by_name(str_replace('合集', '', $name));
		}

		// 泰山归来险战丛林=>丛林
		if(empty($query_res)){
			$query_res = $this->Film_name_model->search_by_name(mb_substr($name, 0, 2));
		}

		if(count($query_res) >= 1){
			if(!empty($director) && strpos($director, '/') !== false){
				$director = mb_substr($director, 0, mb_strpos($director, '/') - 1);
			}
			$douban_ids = array_column($query_res, 'douban_id');

			// 根据actor再次定位
			if(!empty($actors)){
				$query_res = $this->Film_model->query_by_actors_and_douban_id($douban_ids, $actors[0]);
				if(empty($query_res) && !empty($actors[1])){
					$query_res = $this->Film_model->query_by_actors_and_douban_id($douban_ids, $actors[1]);
				}
			}

			if(count($query_res) > 1){
				$actor_locate_douban_ids = array_column($query_res, 'douban_id');

				// 如果actor仍然不能确定唯一, 继续用director
				if(!empty($director)){
					$query_res = $this->Film_model->query_by_director_and_douban_id($actor_locate_douban_ids, $director);
				}

				// 如果actor仍然不能确定唯一, 继续用year
				if(count($query_res) > 1 && !empty($year)){
					$query_res = $this->Film_model->query_by_year_and_douban_id(array_column($query_res, 'douban_id'), $year);
				}
			}else if(count($query_res) == 0){
				// 如果actor出现错误, 用director做替代方案
				if(!empty($director)){
					$query_res = $this->Film_model->query_by_director_and_douban_id($douban_ids, $director);
				}
			}

			if(count($query_res) == 1) {
				$res['count'] = 1;
				$res['film_detail'] = $query_res[0];
			}else{
				$res['count'] = count($query_res);
			}
		}

//		$ex = array(
//			'佛莱迪大战杰森',
//			'分歧/分歧者',// todo
//			'机械师',// todo
//			'佐州自救兄弟', // todo
//			'人猿泰山',// todo year
//			'鼹鼠之歌/卧底威龙', // todo year
//			'东方三侠', // todo year
//			'硬核大战/硬核亨利',
//			'树大招风',
//			'超能太监',
//			'杀死比尔',
//			'杀手之绝命基地',
//			'李小龙电影合集',
//			'次日到达',
//		);
//		if($res['count'] != 1 && !in_array($name, $ex)){
//			echo $lol_url . PHP_EOL;
//			echo $name . PHP_EOL;
//			echo $director . PHP_EOL;
//			print_r($actors);
//			print_r($res);exit;
//		}

		return $res;
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
