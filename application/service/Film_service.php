<?php
class Film_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 * 处理名称
	 * @param $douban_id
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
	public function get_up_films($timestamp){
		$films = array();
		$this->load->model('Film_model');
		$this->load->model('Film_bt_model');
		$up_film_ids = $this->Film_bt_model->query_by_time($timestamp);
		if(!empty($up_film_ids)){
			$up_film_ids = array_column($up_film_ids, 'film_id');
			$films = $this->Film_model->get_by_ids($up_film_ids);
		}

		return $films;
	}


    /**************************************private methods****************************************************************************/


} 