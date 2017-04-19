<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Task extends MY_Controller {

	public function update_post_covers(){
		$page = 1;
		$limit = 30;
		$this->load->model('Film_model');
		while($page < 1){
			sleep(1);
			$films = $this->Film_model->get($page++ * $limit, $limit);
			if(empty($films)){
				break;
			}

			foreach($films as $film){
				if(empty($film['l_post_cover']) || empty($film['b_post_cover'])){
					if(!empty($film['douban_post_cover'])){
						if($this->_update_post_cover($film['douban_id'], $film['douban_post_cover'])){
							echo 'success:' . $film['douban_id'] . PHP_EOL;
						}else{
							echo 'fail:' . $film['douban_id'] . PHP_EOL;
						}
					}else{
						$this->log_error('empty douban post cover:' . $film['douban_id']);
					}
				}
			}
		}
	}

	/**
	 * 修正db里面错误的豆瓣封面
	 */
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
		$start = 0;
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
				$actor_search = '';
				if(!empty($data['actors'])){
					$actors = explode('：', $data['actors']);
					if(count($actors) == 2){
						$actors = explode(' ', $actors[1]);
						if(!empty($actors)) {
							foreach($actors as $tmp){
								if(!empty($tmp)) {
									$actor_search = $tmp;
									break;
								}
							}
						}
					}
				}
				$query_res = $this->_search_unique_film_by_name_actor($data['name'], $actor_search);
				if(empty($query_res['count'])){
					$find_none++;
					echo 'none : ' . $data['name'] . ':' . $actor_search . PHP_EOL;
					$data['find_count'] = 0;
					fputs($wfp, json_encode($data) . PHP_EOL);
				}else if($query_res['count'] == 1){
					$find_one++;
					$query_film = $query_res['film_detail'];
					$insert_bts_array = array();
					foreach($data['thunder'] as $bt){
						array_push($insert_bts_array, array(
							'douban_id' => $query_film['douban_id'],
							'type' => 1,
							'url' => $bt['link'],
							'name' => $bt['title'],
						));
					}
					foreach($data['bt'] as $bt){
						array_push($insert_bts_array, array(
							'douban_id' => $query_film['douban_id'],
							'type' => 2,
							'url' => $bt['link'],
							'name' => $bt['title'],
						));
					}
					foreach($data['magnet'] as $bt){
						array_push($insert_bts_array, array(
							'douban_id' => $query_film['douban_id'],
							'type' => 3,
							'url' => $bt['link'],
							'name' => $bt['title'],
						));
					}
					if(!empty($insert_bts_array)){
						$this->Film_bt_model->insert_batch($insert_bts_array);
						$this->Film_model->update_loldytt_info($query_res['film_detail']['id'], $data);
						echo 'success : ' . $query_res['film_detail']['douban_id'] . PHP_EOL;
					}
				}else {
					$find_multi++;
					echo 'multi : ' . $data['name'] . ':' . $actor_search . PHP_EOL;
					$data['find_count'] = $query_res['count'];
					fputs($wfp, json_encode($data) . PHP_EOL);
				}
			}
		}

		fclose($rfp);
		fclose($wfp);
		echo $find_none . '-' . $find_one . '-' . $find_multi . PHP_EOL;
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


	/************************************************* private methods *************************************************************/

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
		$down_pic_url = str_replace('https', 'http', $douban_post_cover_link);
		$down_pic_file_name = substr($douban_post_cover_link, strrpos($douban_post_cover_link, '/') + 1);

		$b_down_pic_url = $l_down_pic_url  = '';
		if(strpos($down_pic_url, 'ipst') !== false || strpos($down_pic_url, 'lpst') !== false ){
			$b_down_pic_url = str_replace('ipst', 'lpst', $down_pic_url);
			$l_down_pic_url = str_replace('lpst', 'ipst', $down_pic_url);
		}else if(strpos($down_pic_url, 'lpic') !== false || strpos($down_pic_url, 'ipic') !== false){
			$b_down_pic_url = str_replace('spic', 'lpic', $down_pic_url);
			$l_down_pic_url = str_replace('lpic', 'spic', $down_pic_url);
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
					$this->log_error('download fail:' . $douban_id);
				}
			}

			return true;
		}else{
			$this->log_error('ilegal url:' . $douban_id . ';' . $douban_post_cover_link);
			return false;
		}
	}


	/**
	 * 根据名称和演员查询唯一的电影详情
	 * @param $name
	 * @param $actor
	 * @return array
	 */
	private function _search_unique_film_by_name_actor($name, $actor){
		$res = array(
			'count' => 0,
			'film_detail' => array()
		);

		if(strpos($name, '/') !== false){

			$name = substr($name, 0, stripos($name, '/'));
		}
		if(strpos($name, '(') !== false){

			$name = substr($name, 0, stripos($name, '('));
		}

		$this->load->Model('Film_name_model');
		$this->load->Model('Film_model');
		$query_res = $this->Film_name_model->search_by_name($name);
		if(empty($query_res)) {
			$pattern = '#[\s\S]*[\d]#U';
			preg_match($pattern, $name, $matches);
			if(!empty($matches) && !empty($matches[0])){
				$query_res = $this->Film_name_model->search_by_name($matches[0]);
			}
		}
		if(count($query_res) == 0){
			return $res;
		}else if(count($query_res) == 1) {
			$res['count'] = 1;
			$res['film_detail'] = $this->Film_model->get_by_douban_id($query_res[0]['douban_id']);
		}else{
			$douban_ids = array();
			foreach($query_res as $tmp){
				array_push($douban_ids, $tmp['douban_id']);
			}
			$query_res = $this->Film_model->query_by_actors_and_douban_id($douban_ids, $actor);
			if(count($query_res) == 1) {
				$res['count'] = 1;
				$res['film_detail'] = $query_res[0];
			}else{
				$res['count'] = count($query_res);
			}
		}

		return $res;
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
		$this->c_echo('user error :on function ' . $function . ' line ' . $line . ':' . $msg);
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
