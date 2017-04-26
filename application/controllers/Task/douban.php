<?php
class Douban extends MY_Controller {

	public function test(){
		$page = 0;
		$limit = 20;
		$this->load->model('Film_model');
		$this->load->model('Film_name_model');
		while($page < 10000000){
			$films = $this->Film_model->get($page++ * $limit, $limit);
			if(empty($films)){
				echo 'end ' . $page . PHP_EOL;
				break;
			}

			echo $page . PHP_EOL;

			$insert_data = array();
			foreach($films as $tmp){
				if(!empty($tmp['ch_name'])){
					array_push($insert_data, array(
						'name' => $tmp['ch_name'],
						'douban_id' => $tmp['douban_id'],
					));
				}
				if(!empty($tmp['or_name'])){
					array_push($insert_data, array(
						'name' => $tmp['or_name'],
						'douban_id' => $tmp['douban_id'],
					));
				}
			}

			if(!empty($insert_data)){
				$this->Film_name_model->insert_batch($insert_data);
			}
		}
	}

	public function craw_douban_films_by_db_recom_ids(){
		$page = 0;
		$limit = 10;
		$this->load->model('Film_model');
		$this->load->model('Film_recom_model');
		while($page++ < 10000000){
			$un_crawed_douban_ids = $this->Film_recom_model->get_un_crawed_douban_ids(0, $limit);
			if(empty($un_crawed_douban_ids)){
				echo 'end ' . $page . PHP_EOL;
				break;
			}

			foreach($un_crawed_douban_ids as $tmp){
				$douban_id = $tmp['douban_id'];
				echo 'no exist:' . $douban_id . PHP_EOL;
				if($this->_craw_and_store_douban_film($douban_id)){
					echo 'success:' . $douban_id . PHP_EOL;
				}else{
					$this->Film_recom_model->incr_invalid_times($douban_id);
					echo 'fail:' . $douban_id . PHP_EOL;
				}
			}
		}
	}

	public function hand_process_douban($type, $str){
		$douban_id_arr = array();
		if($type == 1){
			$douban_id_arr = explode(':', $str);
		}else if($type == 2){
			$file_content = file_get_contents(str_replace(':', '/', $str));
			$douban_id_arr = explode("\n", $file_content);
		}

		foreach($douban_id_arr as $douban_id){
			if(!empty($douban_id)){
				$this->_craw_and_store_douban_film($douban_id);
			}
		}
	}

	/************************************************* private methods *************************************************************/

	/**
	 * 爬取并存储豆瓣电影(爬取、入库、处理图片、处理海报)
	 * @param $douban_id
	 * @return bool
	 */
	private function _craw_and_store_douban_film($douban_id){
		$this->load->model('Film_model');

		$db_film_detail = $this->Film_model->get_by_douban_id($douban_id);

		// 爬取
		$douban_film_detail = $this->_craw_douban_detail($douban_id);
		if(empty($douban_film_detail)){
			$this->log_error('craw get nothing from ' . $douban_id);
			return false;
		}

		// insert film
		$insert_film_data = array(
			'douban_id' => $douban_film_detail['id'],
			'ch_name' => !empty($douban_film_detail['ch_name'])? $douban_film_detail['ch_name']:'',
			'or_name' => !empty($douban_film_detail['or_name'])? $douban_film_detail['or_name']:'',
//			'other_names' => !empty($douban_film_detail['other_names'])? json_encode($douban_film_detail['other_names']):'',
			'year' => !empty($douban_film_detail['year'])? $douban_film_detail['year']:'',
			'director' => !empty($douban_film_detail['director'])? $douban_film_detail['director']:'',
			'actors' => !empty($douban_film_detail['actors'])? implode(',', $douban_film_detail['actors']):'',
			'genre' => !empty($douban_film_detail['genre'])? implode(',', $douban_film_detail['genre']):'',
			'runtime' => !empty($douban_film_detail['runtime'])? $douban_film_detail['runtime']:'',
			'douban_rate' => !empty($douban_film_detail['rate'])? $douban_film_detail['rate']:'',
			'summary' => !empty($douban_film_detail['summary'])? $douban_film_detail['summary']:'',
			'comments' => !empty($douban_film_detail['comments'])? json_encode($douban_film_detail['comments']):'',
			'recom_douban_id' => !empty($douban_film_detail['recomm_ids'])? implode(',', $douban_film_detail['recomm_ids']):'',
		);

		if(empty($db_film_detail)){
			$affect_rows = $this->Film_model->insert($insert_film_data);
		}else{
			$affect_rows = $this->Film_model->update_by_douban_id($douban_id, $insert_film_data);
		}

		if($affect_rows){
			// process names
			$insert_names = array();
			if(!empty($douban_film_detail['other_names'])){
				$insert_names = $douban_film_detail['other_names'];
			}
			$insert_names[] = $douban_film_detail['ch_name'];
			if(!empty($douban_film_detail['or_name'])){
				$insert_names[] = $douban_film_detail['or_name'];
			}
			$this->_process_film_names($douban_id, $insert_names);

			// handle pic
			if(!empty($douban_film_detail['related_pics'])){
				$this->_process_film_pics($douban_id,  $douban_film_detail['related_pics']);
			}

			// handle post cover
			if(!empty($douban_film_detail['post_cover'])){
				$this->_update_post_cover($douban_id, $douban_film_detail['post_cover']);
			}

			// handle recom
			if(!empty($douban_film_detail['recomm_ids'])){
				$this->_process_film_recom($douban_id, $douban_film_detail['recomm_ids']);
			}
		}

		return true;
	}

	/**
	 * 处理图片
	 * @param $douban_id
	 * @param $recom_ids
	 */
	private function _process_film_recom($douban_id, $recom_ids){
		$insert_data = array();
		$recom_douban_ids = array_unique($recom_ids);
		foreach($recom_douban_ids as $tmp){
			array_push($insert_data, array(
				'douban_id' => $douban_id,
				'recom_douban_id' => $tmp,
			));
		}

		if(!empty($insert_data)){
			$this->load->model('Film_recom_model');
			$this->Film_recom_model->insert_batch($insert_data);
		}
	}

	/**
	 * 处理图片
	 * @param $douban_id
	 * @param $pics
	 */
	private function _process_film_pics($douban_id, $pics){
		$this->load->model('Film_pic_model');
		$exist_file_names = $this->Film_pic_model->get_pics_by_douban_id($douban_id);
		$exist_file_names = array_column($exist_file_names, 'file_name');

		foreach($pics as $pic_url){
			if(!empty($pic_url) && !in_array(substr($pic_url, strrpos($pic_url, '/') + 1), $exist_file_names)){
				$this->_down_and_store_film_pic($douban_id, $pic_url);
			}
		}
	}

	/**
	 * 处理名称
	 * @param $douban_id
	 * @param $names
	 */
	private function _process_film_names($douban_id, $names){
		$insert_names = array();
		foreach($names as $name){
			array_push($insert_names, array(
				'name' => $name,
				'douban_id' => $douban_id,
			));
		}
		$this->load->model('Film_name_model');
		$this->Film_name_model->insert_batch($insert_names);
	}

	/**
		 * 更新封面(下载、上传、更新db)
		 * @param $douban_id
		 * @param $douban_post_cover_link
		 * @return bool|void
		 */
	private function _update_post_cover($douban_id, $douban_post_cover_link)
	{
		if(empty($douban_id) || empty($douban_post_cover_link)){
			return false;
		}
		$this->load->model('Film_model');
		$down_pic_url = str_replace('https', 'http', $douban_post_cover_link);
		$down_pic_file_name = substr($douban_post_cover_link, strrpos($douban_post_cover_link, '/') + 1);

		$b_down_pic_url = $l_down_pic_url  = '';
		if(strpos($down_pic_url, 'ipst') !== false || strpos($down_pic_url, 'lpst') !== false ){
			$b_down_pic_url = str_replace('ipst', 'lpst', $down_pic_url);
			$l_down_pic_url = str_replace('lpst', 'ipst', $down_pic_url);
		}else if(strpos($down_pic_url, 'lpic') !== false || strpos($down_pic_url, 'spic') !== false){
			$b_down_pic_url = str_replace('spic', 'lpic', $down_pic_url);
			$l_down_pic_url = str_replace('lpic', 'spic', $down_pic_url);
		}else if(strpos($down_pic_url, '_default_') !== false){
			$update_info = array('b_post_cover' => 'movie_default_large.png');
			$this->Film_model->update_by_douban_id($douban_id, $update_info);
			$update_info = array('l_post_cover' => 'movie_default_small.png');
			$this->Film_model->update_by_douban_id($douban_id, $update_info);
			return true;
		}

		if(!empty($b_down_pic_url)){
			$pending_down_content = array(
				array(
					'type' => 1,
					'url' => $b_down_pic_url,
					'file_name' => 'pcl_' . $down_pic_file_name,
				),
				array(
					'type' => 2,
					'url' => $l_down_pic_url,
					'file_name' => 'pci_' . $down_pic_file_name,
				)
			);

			foreach($pending_down_content as $tmp){
				$down_pic_url = $tmp['url'];
				$down_pic_file_full_path = '/tmp/' . $tmp['file_name'];
				$cmd = "wget -q {$down_pic_url} -O $down_pic_file_full_path";
				exec($cmd);
				if(file_exists($down_pic_file_full_path) && filesize($down_pic_file_full_path) > 10) {
					// 上传
					if($this->qiniu->upload($down_pic_file_full_path, $tmp['file_name'])){
						$update_info = $tmp['type'] == 1?  array('b_post_cover' => $tmp['file_name'],):array('l_post_cover' => $tmp['file_name'],);
						$this->Film_model->update_by_douban_id($douban_id, $update_info);
					}else{
						$this->log_error('upload fail:' . $douban_id);
					}
					@unlink($down_pic_file_full_path);
				}else{
					$this->log_error('download fail:' . $douban_id . ':' . $down_pic_url);
				}
			}

			return true;
		}else{
			$this->log_error('ilegal url:' . $douban_id . ';' . $douban_post_cover_link);
			return false;
		}
	}

	/**
	 * 下载并上传、存储图片
	 * @param $douban_id
	 * @param $douban_pic_url
	 */
	private function _down_and_store_film_pic($douban_id, $douban_pic_url){
		if(empty($douban_id) || empty($douban_pic_url)){
			return;
		}

		$this->load->model('Film_pic_model');
		$douban_pic_url = str_replace('https', 'http', $douban_pic_url);
		$down_pic_file_name = substr($douban_pic_url, strrpos($douban_pic_url, '/') + 1);
		$down_pic_file_full_path = '/tmp/' . $down_pic_file_name;
		$cmd = "wget -q {$douban_pic_url} -O $down_pic_file_full_path";
		exec($cmd);
		if(file_exists($down_pic_file_full_path) && filesize($down_pic_file_full_path) > 10) {
			// 上传
			if($this->qiniu->upload($down_pic_file_full_path, $down_pic_file_name)){
				$insert_data = array(
					'douban_id' => $douban_id,
					'file_name' => $down_pic_file_name,
				);
				$this->Film_pic_model->insert($insert_data);
			}
			@unlink($down_pic_file_full_path);
		}
	}

	/**
	 * 爬取豆瓣详情页
	 * @param $douban_id
	 * @return array
	 */
	private function _craw_douban_detail($douban_id, $attrs = array()) {
		$ret = array();

		if(empty($douban_id)) {
			return $ret;
		}
		//$html = file_get_contents('/home/wangchuanchuan/tmp/douban_detail.html');
		$douban_link = "https://movie.douban.com/subject/{$douban_id}/";
		$html = $this->_request_douban($douban_link);

		if(strlen($html) < 300 || strpos($html, '你想访问的页面不存在') !== false) {
			$this->log_error('豆瓣页面不存在:' . $douban_id);
			return $ret;
		}

		//
		$ret['link'] = $douban_link;
		$ret['id'] = $douban_id;

		// 主名称
		if(empty($attrs) || in_array('ch_name', $attrs)){
			$pattern = '#<span property="v:itemreviewed">([\s\S]*)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$index = strpos($matches[1], ' ');
				if($index === false) {
					$ret['ch_name'] = $or_name = trim($matches[1]);
				}else {
					$ret['ch_name'] = trim(substr($matches[1], 0, $index));
					$ret['or_name'] = trim(substr($matches[1], $index));
				}
			}

			if(empty($ret['ch_name'])) {
				$this->log_error('no ch_name on ' . $douban_id);
			}
		}

		// 海报
		if(empty($attrs) || in_array('post_cover', $attrs)){
			$pattern = '#<div id="mainpic"([\s\S]*)</div>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$post_cover_html = $matches[1];
				$pattern = '#src="([\s\S]*)"#U';
				$matches = array();
				preg_match($pattern, $post_cover_html, $matches);
				if(!empty($matches)){
					$ret['post_cover'] = $matches[1];
				}
			}
			if(empty($ret['post_cover'])) {
				$this->log_error('no post_cover ' . $douban_id);
			}
		}

		// 年份
		if(empty($attrs) || in_array('year', $attrs)){
			$pattern = '#<span class="year">\(([\s\S]*)\)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['year'] = $matches[1];
			}

			if(empty($ret['year'])) {
				$this->log_error('no year ' . $douban_id);
			}
		}

		// 其他名称
		if(empty($attrs) || in_array('other_names', $attrs)){
			$pattern = '#<span class="pl">又名:</span>([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$names = explode('/', $matches[1]);
				$ret['other_names'] = $names;
			}
		}

		// 导演
		if(empty($attrs) || in_array('director', $attrs)){
			$pattern = '#rel="v:directedBy">([\s\S]*)</a>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['director'] = $matches[1];
			}
		}


		// 主演
		if(empty($attrs) || in_array('actors', $attrs)){
			$pattern = '#<span class="actor">([\s\S]*)<br/>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$actors_html = $matches[1];
				$pattern = '#rel="v:starring">([\s\S]*)</a>#U';
				$matches = array();
				preg_match_all($pattern, $actors_html, $matches);
				if(!empty($matches[1])) {
					$ret['actors'] = $matches[1];
				}
			}
		}

		// 类型
		if(empty($attrs) || in_array('genre', $attrs)){
			$pattern = '#<span property="v:genre">([\s\S]*)</span>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['genre'] = $matches[1];
			}
		}

		// 片长
		if(empty($attrs) || in_array('runtime', $attrs)){
			$pattern = '#<span property="v:runtime" content="(\d+)">#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['runtime'] = $matches[1] . '分钟';
			}
		}

		// 评分
		if(empty($attrs) || in_array('rate', $attrs)){
			$pattern = '#property="v:average">(\d.\d)</strong>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['rate'] = $matches[1];
			}
		}

		// 简介
		if(empty($attrs) || in_array('summary', $attrs)){
			$pattern = '#<span property="v:summary" class="">([\s\S]*)</span>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$ret['summary'] = trim($matches[1]);
			}
		}

		// 相关图片
		if(empty($attrs) || in_array('related_pics', $attrs)){
			$pattern = '#<ul class="related-pic-bd([\s\S]*)</ul>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$related_pics_html = trim($matches[1]);
				$pattern = '#<img src="([\s\S]*)"#U';
				$matches = array();
				preg_match_all($pattern, $related_pics_html, $matches);
				if(!empty($matches[1])) {
					$ret['related_pics'] = array_slice($matches[1], 1);
				}
			}
		}

		// 获奖情况
		if(empty($attrs) || in_array('awards', $attrs)){
			$pattern = '#<ul class="award">([\s\S]*)</ul>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$awards = array();
				foreach($matches[1] as $tmp) {
					$pattern = '#<li>([\s\S]*)</li>#U';
					$matches = array();
					preg_match_all($pattern, $tmp, $matches);
					if(count($matches[1]) === 3) {
						$award = array();
						$award['award'] = substr($matches[1][0], stripos($matches[1][0], '>'), strrpos($matches[1][0], '<') - stripos($matches[1][0], '>') );
						$award['type'] = $matches[1][1];
						$award['who'] = substr($matches[1][2], stripos($matches[1][2], '>'), strrpos($matches[1][2], '<') - stripos($matches[1][2], '>') );
						if(!empty($award)) $awards[] = $award;
					}
				}
				$ret['awards'] = $awards;
			}
		}

		// 推荐
		if(empty($attrs) || in_array('recomm_ids', $attrs)){
			$pattern = '#<div class="recommendations-bd">([\s\S]*)</div>#U';
			$matches = array();
			preg_match($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$recomm_html = trim($matches[1]);
				$pattern = '#href="https://movie.douban.com/subject/(\d+)/\?from=subject-page"#U';
				$matches = array();
				preg_match_all($pattern, $recomm_html, $matches);
				if(!empty($matches[1])) {
					$ret['recomm_ids'] = $matches[1];
				}
			}
		}

		// 评论
		if(empty($attrs) || in_array('comments', $attrs)){
			$pattern = '#<div class="comment">([\s\S]*)</div>#U';
			$matches = array();
			preg_match_all($pattern, $html, $matches);
			if(!empty($matches[1])) {
				$comments = array();
				foreach($matches[1] as $comment_html) {
					$tmp_comment = array();
					$pattern = '#class="">([\s\S]*)</a>#U';
					$matches = array();
					preg_match($pattern, $comment_html, $matches);
					if(!empty($matches[1])) {
						$tmp_comment['user'] = $matches[1];
					}

					$pattern = '#<p class=""> ([\s\S]*)</p>#U';
					$matches = array();
					preg_match($pattern, $comment_html, $matches);
					if(!empty($matches[1])) {
						$tmp_comment['content']= $matches[1];
					}

					if(!empty($tmp_comment['content'])) {
						$comments[] = $tmp_comment;
					}
				}

				$ret['comments'] = $comments;
			}
		}

		return $ret;
	}

	private function c_echo($str)  {
		echo $str . PHP_EOL;
	}

	private function log_error($msg, $function = 0, $line = 0){
		if(!empty($function)){
			$this->c_echo('user error :on function ' . $function . ' line ' . $line . ':' . $msg);
		}else{
			$this->c_echo('user error :' . $msg);
		}

	}

	/**
	 * 带cookie的请求豆瓣
	 * @param $url
	 * @return mixed|string
	 */
	private function _request_douban($url){
		$cookie_file_path = './douban_cookie.txt';
		$cookie_ttl_file_path = './douban_cookie_ttl.txt';
		$start_time = file_get_contents($cookie_ttl_file_path);
		if((time() - $start_time) > 3) {
			file_put_contents($cookie_file_path, '');
			file_put_contents($cookie_ttl_file_path, time());
		}

		$header = array(
			'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'Accept-Encoding:GB2312,utf-8;q=0.7,*;q=0.7',
			'Accept-Language:zh-cn,zh;q=0.5',
			'Host:movie.douban.com',
			'User-Agent:Mozilla/5.0 (Windows NT 5.1; rv:5.0) Gecko/20100101 Firefox/5.0'.
			'Referer:http://movie.douban.com/',
			'Cache-Control:max-age=0',
		);

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

		$proxy = array();
		if(($rand = rand(0, count($proxy_array))) < count($proxy_array)){
			$proxy = $proxy_array[$rand];
		}

		return $this->_curl($url, null, $cookie_file_path, $header, $proxy);
	}

	private function _curl($url, $post_data, $cookie_jar, $header, $proxy){
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
