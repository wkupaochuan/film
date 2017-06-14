<?php

class Match_service extends MY_Service{
	public function __construct(){
		parent::__construct();
		$this->load->model('Film_name_model');
		$this->load->model('Film_model');
		$this->load->model('Lol_film_model');
	}

	public function walk_lol_db($only_empty = false){
		$page = 0;
		$limit = 10;

		$echo_msg = array();
		while($page < 10000){
			echo $page . PHP_EOL;
			$films = $this->Lol_film_model->get_film_by_range($page++ * $limit, $limit);
			if(empty($films)){
				break;
			}

			foreach($films as $film){
				$film['other_names'] = explode('/', $film['other_names'] );

				$film['actors'] = explode('/', $film['actors'] );
				foreach($film['actors'] as &$actor){
					$actor = trim($actor);
				}
				$match_res = $this->Match_service->lol($film);
				$echo_msg[$match_res['match']] = isset($echo_msg[$match_res['match']])? $echo_msg[$match_res['match']]+1:1;
				if($match_res['match'] == 1){
					if($this->Film_model->update_by_id($match_res['film_id'], array('lol_id' => $film['id']))){
						$this->Lol_film_model->update_film_by_id($film['id'], array('film_id' => $match_res['film_id']));
					}
				}
			}
		}

		print_r($echo_msg);
	}

	public function lol($lol_film_detail){
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




}