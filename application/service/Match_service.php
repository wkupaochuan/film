<?php

class Match_service extends MY_Service{
	public function __construct(){
		parent::__construct();
		$this->load->model('Film_name_model');
		$this->load->model('Film_model');
		$this->load->model('Lol_film_model');
		$this->load->service('Lol_craw_service');
		$this->load->model('Film_model');
	}

	public function walk_lol_db($only_un_matched = false){
		$page = 0;
		$limit = 10;

		$echo_msg = array();
		while($page < 10000){
			echo $page . PHP_EOL;
			if($only_un_matched){
				$films = $this->Lol_film_model->get_un_matched_films($page++ * $limit, $limit);
			}else{
				$films = $this->Lol_film_model->get_film_by_range($page++ * $limit, $limit);
			}
			if(empty($films)){
				break;
			}

			foreach($films as $film){
				$film['other_names'] = explode('/', $film['other_names'] );

				$film['actors'] = explode('/', $film['actors'] );
				foreach($film['actors'] as &$actor){
					$actor = trim($actor);
				}
				$match_res = $this->_query_film_by_lol_detail($film);
				$echo_msg[$match_res['match']] = isset($echo_msg[$match_res['match']])? $echo_msg[$match_res['match']]+1:1;
				if($match_res['match'] == 1){
					// 禁止覆盖
					$douban_db_film = $this->Film_model->get_detail_by_id($match_res['film_id']);
					if(!empty($douban_db_film['lol_id']) && $film['id'] != $douban_db_film['lol_id']){
						f_log_error('lol and film matche duplicated lol_id:' . $douban_db_film['lol_id'] . ', film_id: ' . $douban_db_film['id']);
					}else{
						$this->_up_lol_match($match_res['film_id'], $film['id']);
					}
				}
			}
		}

		print_r($echo_msg);
	}

	/**
	 * 手动匹配lol
	 * @param $douban_id
	 * @param $lol_url
	 * @return bool
	 */
	public function lol_manual($douban_id, $lol_url){
		if(empty($douban_id) || empty($lol_url)){
			f_log_error('empty params on lol_manual');
			return false;
		}

		// 获取Lol film detail
		$lol_db_detail = $this->Lol_film_model->get_film_detail_by_url($lol_url);
		if(empty($lol_db_detail)){
			// 抓取
			$this->Lol_craw_service->craw($lol_url);
			$lol_db_detail = $this->Lol_film_model->get_film_detail_by_url($lol_url);
			if(empty($lol_db_detail)){
				f_log_error('no lol film in db when match lol and douban');
				return false;
			}
		}

		// 获取douban film detail
		$douban_db_detail = $this->Film_model->get_by_douban_id($douban_id);
		if(empty($douban_db_detail)){
			// 抓取
			$douban_db_detail = $this->Film_model->get_by_douban_id($douban_id);
			if(empty($douban_db_detail)){
				f_log_error('no douban film in db when match lol and douban');
				return false;
			}
		}

		return $this->_up_lol_match($douban_db_detail['id'], $lol_db_detail['id']);
	}

	/**
	 * 手动增加资源
	 * @param $bt_url
	 * @param $type
	 * @param $film_id
	 * @param $name
	 * @return bool
	 */
	public function me($bt_url, $type, $film_id, $name){
		if(empty($bt_url) || empty($type) || empty($film_id)){
			f_log_error('error');
			return false;
		}

		$this->load->model('Film_bt_model');
		$this->load->model('Film_bt_batch_model');

		$exist_bt = $this->Film_bt_model->get_by_urls(array($bt_url));
		if(!empty($exist_bt)){
			f_log_error('已存在');
			return false;
		}

		$batch_id = $this->Film_bt_batch_model->insert(array(
			'type' => $type
		));

		if($batch_id){
			$bt_id = $this->Film_bt_model->insert(array(
				'film_id' =>$film_id,
				'batch_id' => $batch_id,
				'url' => $bt_url,
				'name' => $name,
			));
			if($bt_id){
				$this->Film_model->update_by_id($film_id, array(
					'download_able' => 1
				));
			}else{
				f_log_error('bt insert fail');
			}
		}else{
			f_log_error('batch insert fail');
			return false;
		}

		return true;
	}

	/************************************************* private methods *************************************************************/

	/**
	 * 预处理Lol电影名称
	 * @param $name
	 * @return mixed|string
	 */
	private function _process_lol_name($name){
		// 金刚狼3:殊死一战 => 殊死一战
		if(strpos($name, ':') !== false){
			$name = mb_substr($name, mb_strpos($name, ':') + 1);
			return $name;
		}

		// 逃出克隆岛/神秘岛 => 逃出克隆岛
		if(strpos($name, '/') !== false){
			$name = mb_substr($name, 0, mb_strpos($name, '/'));
			return $name;
		}

		// 新花木兰 => 花木兰
		if(mb_strpos($name, '新') === 0){
			$name = mb_substr($name, 1);
			return $name;
		}

		// 铁甲衣2浴血奋战 => 浴血奋战
		$tmp = explode_by_num($name);
		if(count($tmp) == 3 && is_numeric($tmp[1])){
			$name = $tmp[2];
		}

		// 时间.猎杀者 => 时间·猎杀者
		if(strpos($name, '.') !== false){
			$name = str_replace('.', '·', $name);
			return $name;
		}

		return $name;
	}

	/**
	 * 根据lol详情匹配
	 * @param $lol_film_detail
	 * @return array
	 */
	private function _query_film_by_lol_detail($lol_film_detail){
		$match_result = 0; // 0--no match, 1--match, 2--actor no match, 3--multi
		$match_film_id = 0;

		if(empty(trim($lol_film_detail['ch_name']))){
			f_log_error('empty ch_name on match lol' . json_encode($lol_film_detail));
		}

		// 先用所有名字查一遍
		$search_names = array();
		foreach($lol_film_detail['other_names'] as $tmp_name){
			$tmp_name = trim($tmp_name);
			!empty($tmp_name) && $search_names[] = $tmp_name;
		}
		$search_names[] = trim($lol_film_detail['ch_name']);
		$search_names[] = trim($this->_process_lol_name($lol_film_detail['ch_name']));

		$search_res = $this->Film_name_model->search_by_names($search_names);
		$search_res = array_unique(array_column($search_res, 'film_id'));

		if(count($search_res) == 1){
			$match_result = 1;
			$match_film_id = $search_res[0];
		}else if (count($search_res) > 1){
			$match_result = 3;
			if(!empty($lol_film_detail['actors'][0])){
				$actor_search_res = $this->Film_model->query_by_actors_and_id($search_res, $lol_film_detail['actors'][0]);
				if(count($actor_search_res) == 0){
					$match_result = 2;
				}else if(count($actor_search_res) == 1){
					$match_result = 1;
					$match_film_id = $actor_search_res[0]['id'];
				}
			}
		}

		return array(
			'match' => $match_result,
			'film_id' => $match_film_id
		);
	}

	/**
	 * 更新lol对应关系
	 * @param $film_id
	 * @param $lol_id
	 * @return bool
	 */
	private function _up_lol_match($film_id, $lol_id){
		if(empty($film_id) || empty($lol_id)){
			return false;
		}
		if($this->Film_model->update_by_id($film_id, array('lol_id' => $lol_id))){
			return $this->Lol_film_model->update_film_by_id($lol_id, array('film_id' => $film_id));
		}

		return false;
	}
}