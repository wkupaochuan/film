<?php
class Film_recom_service extends MY_Service{

    /**************************************public methods****************************************************************************/

	/**
	 * @param $film_id
	 * @param $recom_ids
	 * @return bool
	 */
	public function process_douban_recom($film_id, $recom_ids){
		if(empty($film_id) || empty($recom_ids)){
			return false;
		}
		$insert_data = array();
		$recom_douban_ids = array_unique($recom_ids);
		foreach($recom_douban_ids as $tmp){
			array_push($insert_data, array(
				'film_id' => $film_id,
				'recom_douban_id' => $tmp,
			));
		}

		if(!empty($insert_data)){
			$this->load->model('Film_recom_model');
			$this->Film_recom_model->insert_batch($insert_data);
		}

		// 保存未抓取的豆瓣id
		$this->_process_un_douban($recom_ids);
	}

	/**
	 * 标记已抓取
	 * @param $douban_id
	 */
	public function up_un_douban($douban_id){
		$this->load->model('Un_douban_model');
		$this->Un_douban_model->mark($douban_id);
	}

    /**************************************private methods****************************************************************************/

	/**
	 * 保存未抓取的豆瓣id
	 * @param $douban_ids
	 */
	private function _process_un_douban($douban_ids){
		$this->load->model('Film_model');
		$db_douban_arr = $this->Film_model->get_by_douban_ids($douban_ids);
		$db_douban_ids = array_column($db_douban_arr, 'douban_id');
		$insert_data = array();
		foreach($douban_ids as $douban_id){
			if(!in_array($douban_id, $db_douban_ids)){
				$insert_data[] = array(
					'douban_id' => $douban_id
				);
			}
		}

		$this->load->model('Un_douban_model');
		$this->Un_douban_model->insert_batch($insert_data);
	}

} 