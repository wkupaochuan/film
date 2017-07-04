<?php
class Film_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	public function __construct(){
		parent::__construct();
		$this->load->model('Film_model');
		$this->load->model('Film_pic_model');
        $this->load->model('Film_ac_log_model');
		$this->load->service('Lol_service');
	}

	/**
	 * 处理名称
	 * @param $film_id
	 * @param $names
	 */
	public function process_film_names($film_id, $names){
		$insert_names = array();
		foreach($names as $name){
			array_push($insert_names, array(
				'name' => $name,
				'film_id' => $film_id,
			));
		}
		$this->load->model('Film_name_model');
		$this->Film_name_model->insert_batch($insert_names);
	}

	/**
	 * 获取最近更新的films
	 * @param $timestamp
	 * @return array
	 */
	public function get_up_films($timestamp, $limit = 24){
		$this->load->model('Film_model');
		return $this->Film_model->query_by_up_time($timestamp, $limit);
	}

	/**
	 * 根据id更新
	 * @param $film_id
	 * @param $data
	 * @return bool
	 */
	public function update_film_by_id($film_id, $data){
		if(empty($film_id)){
			return false;
		}

		return $this->Film_model->update_by_id($film_id, $data);
	}

	/**
	 * 获取电影详情
	 * @param $film_id
	 * @return array
	 */
	public function get_film_detail($film_id){
		$film_detail = array();
		if(empty($film_id)){
			return  $film_detail;
		}

		$film_detail = $this->Film_model->get_detail_by_id($film_id);
		if(empty($film_detail)){
			return $film_detail;
		}

		$film_detail['actors'] = str_replace(',', '/', $film_detail['actors']);
		$film_detail['genre'] = str_replace(',', ' / ', $film_detail['genre']);
		$film_detail['other_names'] = !empty($film_detail['other_names'])? json_decode($film_detail['other_names'], true):array();
		$film_detail['comments'] = !empty($film_detail['comments'])? json_decode($film_detail['comments'], true):array();
		$film_detail['related_pics'] = $this->Film_pic_model->get_by_film_id($film_detail['id']);
		!empty($film_detail['related_pics']) && $film_detail['related_pics'] = array_slice($film_detail['related_pics'], 0, 6);
 		!empty($film_detail['recom_douban_id']) && $film_detail['recom_films'] = $this->Film_model->get_recom_films($film_detail['id']);

		$all_bts = array();

		if(!empty($film_detail['lol_id'])){
			$all_bts = f_array_append($all_bts, $this->Lol_service->get_bts($film_detail['lol_id']));
		}
		// todo batch_id 重复
		$this->load->model('Film_bt_model');
		$all_bts = f_array_append($all_bts, $this->Film_bt_model->get_by_film_id($film_id));

		$sorted_bts = array(
			'thunder' => array(),
			'bt' => array(),
			'mag' => array(),
		);
		if(!empty($all_bts)){
			foreach($all_bts as $tmp){
				if($tmp['type'] == 1){
					$sorted_bts['thunder'][$tmp['batch_id']][] = $tmp;
				}else if($tmp['type'] == 2){
					$sorted_bts['bt'][$tmp['batch_id']][] = $tmp;
				}else if($tmp['type'] == 3){
					$sorted_bts['mag'][$tmp['batch_id']][] = $tmp;
				}
			}
		}
		$film_detail['bt'] = $sorted_bts;

		// 更新访问日志
		if(!from_robot()){
			$this->load->model('Film_ac_log_model');
			$this->Film_ac_log_model->ac($film_id, time(), 1);
		}

		return $film_detail;
	}

    /**
     * 获取最近热门
     * @return mixed
     */
    public function get_last_week_hot_films(){
        $ret = array();
        $hot_film_ids = $this->Film_ac_log_model->get_hot_films(time() - 86400*7, time(), 30);
        if(!empty($hot_film_ids)){
            $hot_film_ids = array_column($hot_film_ids, 'film_id');
            $hot_films = $this->Film_model->get_by_ids(
	            $hot_film_ids,
                array('id', 'douban_id', 'ch_name', 'l_post_cover', 'b_post_cover', 'douban_post_cover'),
                array('download_able' => 1)
            );

	        foreach($hot_films as $film){
		        $hot_films[$film['id']] = $film;
	        }

	        foreach($hot_film_ids as $film_id){
		        if(isset($hot_films[$film_id]))  $ret[] = $hot_films[$film_id];
	        }
        }

        return $ret;
    }

	/**
	 * 获取最近热门无资源的内容
	 * @return mixed
	 */
	public function get_hot_and_un_match_films(){
		$films = $this->Film_ac_log_model->get_hot_and_un_match_films();

		foreach($films as &$film){
			$film['other_names'] = !empty($film['other_names'])? implode('/',json_decode($film['other_names'])):'';
		}



		return $films;
	}

	/**
	 * 用户订阅
	 * @param $data
	 */
	public function user_rs($data){
		$this->load->model('User_rs_model');
		return $this->User_rs_model->insert($data);
	}

    /**************************************private methods****************************************************************************/

}