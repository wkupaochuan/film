<?php
class S80 extends MY_Controller {

	/**
	 * php index.php Task S80 hand_process 'dm:20255' 26268494
	 * @param $s80_url
	 * @param $douban_id
	 */
	public function hand_process($s80_url, $douban_id){
		$s80_url = str_replace(':', '/', $s80_url);
		$this->_craw_and_store($s80_url, $douban_id);
	}

	/************************************************* private methods *************************************************************/

	private function _craw_and_store($url, $douban_id){
		$this->load->model('Film_model');
		$db_film = $this->Film_model->get_by_douban_id($douban_id);
		if(!empty($db_film) && $db_film['info_from'] == 1){
			$this->_log_error('douban film exist already');
			return;
		}

		$douban_film_detail = $this->_craw_film_detail($url);
		if(empty($douban_film_detail)){
			$this->_log_error('get nothing from url ' . $url);
		}

		// insert film
		$insert_film_data = array(
			'douban_id' => $douban_id,
			'ch_name' => !empty($douban_film_detail['ch_name'])? $douban_film_detail['ch_name']:'',
			'year' => !empty($douban_film_detail['year'])? $douban_film_detail['year']:'',
			'director' => !empty($douban_film_detail['directors'])? implode(',', $douban_film_detail['directors']):'',
			'actors' => !empty($douban_film_detail['actors'])? implode(',', $douban_film_detail['actors']):'',
			'summary' => !empty($douban_film_detail['summary'])? $douban_film_detail['summary']:'',
			'info_from' => 2,
		);

		$film_id = null;
		if(empty($db_film)){
			$film_id = $this->Film_model->insert($insert_film_data);
		}else{
			$film_id = $db_film['id'];
			$this->Film_model->update_by_douban_id($douban_id, $insert_film_data);
		}

		if($film_id){
			// process names
			$insert_names = array();
			if(!empty($douban_film_detail['other_names'])){
				$insert_names = $douban_film_detail['other_names'];
			}
			$insert_names[] = $douban_film_detail['ch_name'];
			$this->load->service('Film_service');
			$this->Film_service->process_film_names($film_id, $insert_names);

			// handle post cover
			if(!empty($douban_film_detail['post_cover'])){
				$this->_update_post_cover($douban_id, $douban_film_detail['post_cover']);
			}
		}

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

		$b_down_pic_url = $l_down_pic_url  = $down_pic_url;

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
						$this->_log_error('upload fail:' . $douban_id);
					}
					@unlink($down_pic_file_full_path);
				}else{
					$this->_log_error('download fail:' . $douban_id . ':' . $down_pic_url);
				}
			}

			return true;
		}else{
			$this->_log_error('ilegal url:' . $douban_id . ';' . $douban_post_cover_link);
			return false;
		}
	}

	/**
	 * 爬取页面详情
	 * @param $url
	 * @return array
	 */
	private function _craw_film_detail($url){
		$film_detail = array();
		$url = 'http://www.80s.tw/' . $url;
		$html = $this->_request_80s($url);

		if(strlen($html) < 300){
			$this->_log_error('get nothing from url ' . $url);
			return $film_detail;
		}

		// ch name
		$pattern = '#<h1 class="font14w">([\s\S]*)</h1>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$film_detail['ch_name'] = $matches[1];
		}else{
			$this->_log_error('no ch_name on url ' . $url);
			return $film_detail;
		}

		// other names
		$pattern = '#<span class="font_888">又名：</span>([\s\S]*)</span>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$other_names = explode(',', $matches[1]);
			if(!empty($other_names)){
				$film_detail['other_names'] = $other_names;
			}
		}

		// other names
		$pattern = '#<span class="font_888">又名：</span>([\s\S]*)</span>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$other_names = explode(',', $matches[1]);
			if(!empty($other_names)){
				$film_detail['other_names'] = $other_names;
				foreach($film_detail['other_names'] as &$name){
					$name = trim($name);
				}
			}
		}

		// actors
		$pattern = '#<span class="font_888">演员：</span>([\s\S]*)</span>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$actors_html = $matches[1];
			$pattern = '#title="查看“([\s\S]*)”影片专辑">#U';
			$matches = array();
			preg_match_all($pattern, $actors_html, $matches);
			if(!empty($matches) && !empty($matches[1])){
				$film_detail['actors'] = $matches[1];
			}
		}

		// directors
		$pattern = '#<span class="font_888">导演：</span>([\s\S]*)</span>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$directors_html = $matches[1];
			$pattern = '#>([\s\S]*)</a>#U';
			$matches = array();
			preg_match_all($pattern, $directors_html, $matches);
			if(!empty($matches) && !empty($matches[1])){
				$film_detail['disrectors'] = $matches[1];
			}
		}

		// year
		$pattern = '#<span class="font_888">上映日期：</span>([\s\S]*)</span>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$film_detail['year'] = date('Y', strtotime($matches[1]));
		}

		// summary
		$pattern = '#<span class="font_888">剧情介绍：</span>([\s\S]*)</div>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$film_detail['summary'] = trim(mb_substr($matches[1], 0, mb_strpos($matches[1], '<a')));
		}

		// post_cover
		$pattern = '#<div class="img">([\s\S]*)</div>#U';
		$matches = array();
		preg_match($pattern, $html, $matches);
		if(!empty($matches) && !empty($matches[1])){
			$post_cover_html = $matches[1];
			$pattern = '#<img src="//([\s\S]*)" alt=#U';
			$matches = array();
			preg_match($pattern, $post_cover_html, $matches);
			if(!empty($matches) && !empty($matches[1])){
				$film_detail['post_cover'] = 'http://' . $matches[1];
			}
		}

		return $film_detail;
	}

	/**
	 * 请求80s页面
	 * @param $url
	 * @return mixed|string
	 */
	private function _request_80s($url){
		$cookie_file_path = './80s_cookie.txt';
		static $cookie_time;
		if(empty($cookie_time) || (time() - $cookie_time) > 3){
			$cookie_time = time();
			file_put_contents($cookie_file_path, '');
		}

		return f_curl($url, array(), $cookie_file_path);
	}
}
