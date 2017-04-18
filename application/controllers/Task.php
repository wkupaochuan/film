<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task extends MY_Controller {

	public function modify_douban_post_covers(){
		$page = 1;
		$limit = 200;
		$this->load->model('Film_model');
		while($page < 10){
			$films = $this->Film_model->get($page++ * $limit, $limit);
			if(empty($films)){
				break;
			}

			foreach($films as $film){
				if(empty($film['douban_post_cover']) ||
					(strpos($film['douban_post_cover'], 'ipst') === false && strpos($film['douban_post_cover'], 'lpst') === false &&
						strpos($film['douban_post_cover'], 'lpic') === false && strpos($film['douban_post_cover'], 'movie_default_large') === false)) {
					$film_detail_from_douban = $this->_craw_douban_detail($film['douban_id'], array('post_cover'));
					if(!empty($film_detail_from_douban)){
						$this->Film_model->update_douban_post_cover($film['id'], $film_detail_from_douban['post_cover']);
						echo 'success update doubanid :' . $film['douban_id'] . PHP_EOL;
					}else{
						$this->log_error('get nothing from douban:' . $film['douban_id']);
					}
				}
			}
		}
	}

	/**
	 * update海报
	 * @param $data_dir
	 */
	public function download_post_covers($data_dir)
	{
		$data_dir = str_replace(':', '/', $data_dir);
		$this->load->model('Film_pic_model');
		$limit = 10;
		for($page = 0; ; $page++){
			sleep(1);
			if($page > 100000000) {
				break;
			}
			$pics = $this->Film_pic_model->get_pics($page * $limit, $limit);
			if(empty($pics)) {
				break;
			}

			foreach($pics as $tmp) {
				if(empty($tmp['file_name']) && !empty($tmp['douban_url']) && !empty($tmp['id']) && empty($tmp['file_name']) ) {
					$or_pic_url = $tmp['douban_url'];
					$down_pic_url = str_replace('https', 'http', $or_pic_url);
					$down_pic_file_name = substr($down_pic_url, strrpos($down_pic_url, '/') + 1);
					$down_pic_file_full_path = rtrim($data_dir, '/') . '/' . $down_pic_file_name;
					$cmd = "wget -q {$down_pic_url} -O $down_pic_file_full_path";
					exec($cmd);
					if(file_exists($down_pic_file_full_path)) {
						$this->Film_pic_model->update_file_name($tmp['id'], $down_pic_file_name);
					}
				}
			}
		}
	}

	public function import_dytt_bts($data_path, $output_path)
	{
		$data_path = str_replace(':', '/', $data_path);
		$output_path = str_replace(':', '/', $output_path);
		$rfp = fopen($data_path, 'r');
		$wfp = fopen($output_path, 'w');
		$this->load->model('Film_model');
		$this->load->model('Film_bt_model');

		$i = 0;
		$start = 1;
		$find_none = $find_one = $find_multi = 0;
		while(!feof($rfp)) {
			if($i++ > 1000000) {
				break;
			}

			if($i < $start) continue;

			$line = trim(fgets($rfp));
			if(empty($line)) {
				continue;
			}

			$data = json_decode($line, true);
			if(empty($data) || !is_array($data)) {
				echo '空:' . PHP_EOL . $line . PHP_EOL;
			}

			if(!empty($data['name'])) {
				$query_res = $this->Film_model->query_by_name($data['name']);
				if(empty($query_res)){
					$find_none++;
					$data['find_count'] = 0;
					fputs($wfp, json_encode($data) . PHP_EOL);
				}else if(count($query_res) == 1){
					$find_one++;
					foreach($data['thunder'] as $bt){
						$insert_data = array(
							'type' => 1,
							'url' => $bt['link'],
							'name' => $bt['title'],
						);
						$this->Film_bt_model->insert($insert_data);
					}
					foreach($data['bt'] as $bt){
						$insert_data = array(
							'type' => 2,
							'url' => $bt['link'],
							'name' => $bt['title'],
						);
						$this->Film_bt_model->insert($insert_data);
					}
					foreach($data['magnet'] as $bt){
						$insert_data = array(
							'type' => 3,
							'url' => $bt['link'],
							'name' => $bt['title'],
						);
						$this->Film_bt_model->insert($insert_data);
					}
					$this->Film_model->update_loldytt_info($query_res[0]['id'], $data);
				}else {
					$find_multi++;
					$data['find_count'] = count($query_res);
					fputs($wfp, json_encode($data) . PHP_EOL);
				}
			}
		}

		fclose($rfp);
		fclose($wfp);
		echo $find_none . '-' . $find_one . '-' . $find_multi;
	}

	/**
	 * 导入豆瓣推荐到db
	 * @param $data_path
	 */
	public function import_recom_douban_ids($data_path)
	{
		$data_path = str_replace(':', '/', $data_path);
		$fp = fopen($data_path, 'r');
		$this->load->model('Film_model');

		$i = 0;
		while(!feof($fp)) {
			if($i > 10000000000) {
				break;
			}

			$i++;

			$line = trim(fgets($fp));
			if(empty($line)) {
				continue;
			}

			$data = json_decode($line, true);
			if(empty($data) || !is_array($data)) {
				echo '空:' . PHP_EOL . $line . PHP_EOL;
			}

			if(!empty($data['recomm_ids'])) {
				$this->Film_model->update_recom_ids(implode(',', $data['recomm_ids']), $data['id']);
			}
		}

		fclose($fp);
	}

	/**
	 * 图片到七牛
	 * @param $data_dir
	 */
	public function upload_pic_qiniu($data_dir)
	{
		$data_dir = str_replace(':', '/', $data_dir);
		$dp = opendir($data_dir);
		$i = 0;
		while(($file_name = readdir($dp)) !== false) {
			$name = $file_name;
			$file_path = $data_dir . '/' . $file_name;
			if(is_file($file_path) && filesize($file_path) > 100) {
				if($i++ > 1000) {
					break;
				}
				$upload_res = $this->qiniu->upload($file_path, $name);
				if($upload_res) {
					unlink($file_path);
				}
			}
		}
	}

	/**
	 * 下载豆瓣图片
	 * @param $data_dir
	 */
	public function download_douban_pics($data_dir)
	{
		$data_dir = str_replace(':', '/', $data_dir);
		$this->load->model('Film_pic_model');
		$limit = 10;
		for($page = 0; ; $page++){
			sleep(1);
			if($page > 100000000) {
				break;
			}
			$pics = $this->Film_pic_model->get_pics($page * $limit, $limit);
			if(empty($pics)) {
				break;
			}

			foreach($pics as $tmp) {
				if(empty($tmp['file_name']) && !empty($tmp['douban_url']) && !empty($tmp['id']) && empty($tmp['file_name']) ) {
					$or_pic_url = $tmp['douban_url'];
					$down_pic_url = str_replace('https', 'http', $or_pic_url);
					$down_pic_file_name = substr($down_pic_url, strrpos($down_pic_url, '/') + 1);
					$down_pic_file_full_path = rtrim($data_dir, '/') . '/' . $down_pic_file_name;
					$cmd = "wget -q {$down_pic_url} -O $down_pic_file_full_path";
					exec($cmd);
					if(file_exists($down_pic_file_full_path)) {
						$this->Film_pic_model->update_file_name($tmp['id'], $down_pic_file_name);
					}
				}
			}
		}
	}

	/**
	 * 导入豆瓣图片url到db
	 * @param $data_path
	 */
	public function import_douban_film_pics($data_path)
	{
		$data_path = str_replace(':', '/', $data_path);
		$fp = fopen($data_path, 'r');
		$this->load->model('Film_pic_model');

		$i = 0;
		while(!feof($fp)) {
			if($i > 10000000000) {
				break;
			}

			$i++;

			$line = trim(fgets($fp));
			if(empty($line)) {
				continue;
			}

			$data = json_decode($line, true);
			if(empty($data) || !is_array($data)) {
				echo '空:' . PHP_EOL . $line . PHP_EOL;
			}

			if(!empty($data['related_pics'])) {
				foreach($data['related_pics'] as $pic_url) {
					$this->Film_pic_model->insert(array(
						'douban_id' => $data['id'],
						'douban_url' => $pic_url,
					));
				}
			}
		}

		fclose($fp);
	}

	public function insertFilms(){
		$file_path = '/home/wangchuanchuan/tmp/film_detail.data';
		$fp = fopen($file_path, 'r');
		$counter = 0;
		while($line = fgets($fp)) {
			$line = trim($line);
			if(empty($line)) {
				break;
			}
			if($counter > 100) break;
			$data = json_decode($line, true);
			$this->load->model('Film_model');
			$this->Film_model->insert($data);
			$counter++;
		}
		fclose($fp);
	}

	public function douban_list() {
		$file_path = '/home/wangchuanchuan/tmp/douban_film_list.data';
		$fp = fopen($file_path, 'a+');
		$base_url = 'https://movie.douban.com/tag/';
		for($j = 1900; $j < 2018; $j++) {
			$tmp_url = $base_url . $j . '?type=T&start=';
			$page_url = $tmp_url . '0';
			$total_page = $this->getTotalPage($page_url);
			for($i = 0; $i < $total_page ; $i++) {
				if($i%10 == 0) {
					sleep(1);
				}
				$start = $i * 20;
				$url = $tmp_url . $start;
				$data = file_get_contents($url);
				if(strlen($data) < 100) {
					echo $data;
					break;
				}
				$tmp = $this->parseDouban($data);
				fputs($fp, json_encode($tmp) . PHP_EOL);
			}
		}
		fclose($fp);
	}

	private function getTotalPage($url) {
		$html = file_get_contents($url);
		$pattern = '#data-total-page="([\s\S]*)"#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(empty($matches) || empty($matches[1])) {
			return 1;
		}else{
			return $matches[1];
		}
	}

	private function parseDouban($html)
	{
		$ret = array();

		if(empty($html) || strlen($html) < 100) {
			return $ret;
		}

		$pattern = '#<table width="100%" class="">([\s\S]*)</table>#U';
		$matches = array();
		preg_match_all($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])) {
			foreach($matches[1] as $film_html) {
				// name
				$pattern = '#title="([\s\S]*)"#U';
				$matches = array();
				preg_match($pattern, $film_html, $matches);
				$name = $matches[1];

				// link
				$pattern = '#href="([\s\S]*)"#U';
				$matches = array();
				preg_match($pattern, $film_html, $matches);
				$link = $matches[1];

				// post_cover
				$pattern = '#src="([\s\S]*)"#U';
				$matches = array();
				preg_match($pattern, $film_html, $matches);
				$post_cover = $matches[1];

				$ret[] = array(
					'name' => $name,
					'link' => $link,
					'post_cover' => $post_cover,
				);
			}
		}

		return $ret;
	}

	private function _update_post_cover($douban_id, $douban_post_cover_link)
	{
		if(empty($douban_id) || emtpy($douban_post_cover_link)){
			return;
		}
		$down_pic_url = str_replace('https', 'http', $douban_post_cover_link);
		$down_pic_file_name = substr($douban_post_cover_link, strrpos($douban_post_cover_link, '/') + 1);
		$down_pic_file_full_path = '/tmp/' . $down_pic_file_name;
		$cmd = "wget -q {$down_pic_url} -O $down_pic_file_full_path";
		exec($cmd);
		if(file_exists($down_pic_file_full_path)) {
			$this->Film_pic_model->update_file_name($tmp['id'], $down_pic_file_name);
		}
	}



	/************************************************* private methods *************************************************************/
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
		$this->c_echo('user error :on function ' . $function . ' line ' . $line . ':' . $msg);
	}

	private function _request_douban($url){
		$cookie_file_path = './douban_cookie.txt';
		$cookie_ttl_file_path = './douban_cookie_ttl.txt';
		$start_time = file_get_contents($cookie_ttl_file_path);
		if((time() - $start_time) > 5) {
			file_put_contents($cookie_file_path, '');
			file_put_contents($cookie_ttl_file_path, time());
		}

		return $this->_curl($url, null, $cookie_file_path);
	}

	private function _curl($url, $post_data, $cookie_jar){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		if(!empty($post_data)) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		if(!empty($cookie_jar)) {
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
		}

		$res  = curl_exec($ch);
		$http_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errno     = curl_errno($ch);
		$errmsg    = (0 != $errno) ? curl_error($ch) : '';
		curl_close($ch);

		if($http_code != 200 || $errno != 0) {
			$this->log_error('curl fail.errno:' .  $errno . ';errmsg:' . $errmsg . ';url:' . $url);
			return '';
		}else{
			return $res;
		}
	}
}
